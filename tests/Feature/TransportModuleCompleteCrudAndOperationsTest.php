<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\SchoolFeeStructure;
use App\Models\StudentTransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\TransportStaff;
use App\Models\TransportTrip;
use App\Models\TransportTripPassenger;
use App\Models\TransportVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportModuleCompleteCrudAndOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student1;

    protected User $student2;

    protected AcademicClass $class10A;

    protected function setUp(): void
    {
        parent::setUp();

        AcademySetting::set('academic_session', '2026-2027');

        $this->admin = User::factory()->create([
            'name' => 'Transport Director',
            'email' => 'admin@primearc.academy',
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Teacher Rao',
            'email' => 'teacher@primearc.academy',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->class10A = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'academic_year' => '2026-2027',
            'capacity' => 40,
            'status' => 'active',
        ]);

        SchoolFeeStructure::create([
            'class_id' => $this->class10A->id,
            'fee_title' => 'Tuition Fee',
            'fee_type' => 'tuition',
            'amount' => 5000.00,
            'due_frequency' => 'monthly',
            'academic_year' => '2026-2027',
        ]);

        $this->student1 = User::factory()->create([
            'name' => 'Dev Sharma',
            'email' => 'dev@student.academy',
            'role' => 'student',
            'admission_number' => 'ADM-2026-0001',
            'current_class_id' => $this->class10A->id,
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student1->id,
            'class_id' => $this->class10A->id,
            'academic_year' => '2026-2027',
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $this->student2 = User::factory()->create([
            'name' => 'Priya Patel',
            'email' => 'priya@student.academy',
            'role' => 'student',
            'admission_number' => 'ADM-2026-0002',
            'current_class_id' => $this->class10A->id,
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student2->id,
            'class_id' => $this->class10A->id,
            'academic_year' => '2026-2027',
            'roll_number' => 2,
            'status' => 'active',
        ]);
    }

    /**
     * 1. Test Vehicles CRUD & validation.
     */
    public function test_vehicles_crud_and_status_changes(): void
    {
        // CREATE
        $createRes = $this->actingAs($this->admin)->post(route('school.transport.vehicles.store'), [
            'vehicle_number' => 'DL-01-AB-1234',
            'vehicle_type' => 'Bus',
            'capacity' => 45,
            'vehicle_model' => 'Tata Starbus 45',
            'year_made' => '2025',
            'status' => 'active',
        ]);
        $createRes->assertRedirect(route('school.transport.index', ['tab' => 'vehicles']));
        $this->assertDatabaseHas('transport_vehicles', ['vehicle_number' => 'DL-01-AB-1234', 'capacity' => 45]);

        $vehicle = TransportVehicle::where('vehicle_number', 'DL-01-AB-1234')->first();

        // Prevent duplicate vehicle number
        $dupRes = $this->actingAs($this->admin)->post(route('school.transport.vehicles.store'), [
            'vehicle_number' => 'DL-01-AB-1234',
            'vehicle_type' => 'Bus',
            'capacity' => 40,
            'status' => 'active',
        ]);
        $dupRes->assertSessionHasErrors('vehicle_number');

        // UPDATE
        $updateRes = $this->actingAs($this->admin)->put(route('school.transport.vehicles.update', $vehicle->id), [
            'vehicle_number' => 'DL-01-AB-1234',
            'vehicle_type' => 'Minibus',
            'capacity' => 32,
            'vehicle_model' => 'Eicher Skyline',
            'status' => 'maintenance',
        ]);
        $updateRes->assertRedirect(route('school.transport.index', ['tab' => 'vehicles']));
        $this->assertDatabaseHas('transport_vehicles', ['id' => $vehicle->id, 'capacity' => 32, 'status' => 'maintenance']);

        // DELETE
        $delRes = $this->actingAs($this->admin)->delete(route('school.transport.vehicles.destroy', $vehicle->id));
        $delRes->assertRedirect(route('school.transport.index', ['tab' => 'vehicles']));
        $this->assertDatabaseMissing('transport_vehicles', ['id' => $vehicle->id]);

        // Audit log verified
        $this->assertDatabaseHas('transport_audit_logs', ['action' => 'deleted', 'entity_type' => 'vehicle']);
    }

    /**
     * 2. Test Routes CRUD & Stop Sequence Uniqueness.
     */
    public function test_routes_and_stops_crud(): void
    {
        // CREATE ROUTE
        $res = $this->actingAs($this->admin)->post(route('school.transport.routes.store'), [
            'route_title' => 'North Delhi Corridor',
            'fare' => 1800.00,
            'start_point' => 'Rohini Sector 18',
            'end_point' => 'Main Campus Gate',
            'distance_km' => 16.5,
            'status' => 'active',
        ]);
        $res->assertRedirect(route('school.transport.index', ['tab' => 'routes']));
        $route = TransportRoute::where('route_title', 'North Delhi Corridor')->first();
        $this->assertNotNull($route);

        // CREATE STOP #1
        $stopRes1 = $this->actingAs($this->admin)->post(route('school.transport.stops.store'), [
            'route_id' => $route->id,
            'stop_name' => 'Pitampura Metro',
            'stop_sequence' => 1,
            'pickup_time' => '07:00 AM',
            'drop_time' => '03:30 PM',
        ]);
        $stopRes1->assertRedirect();
        $this->assertDatabaseHas('transport_route_stops', ['stop_name' => 'Pitampura Metro', 'stop_sequence' => 1]);

        // PREVENT DUPLICATE STOP SEQUENCE ON SAME ROUTE
        $stopResDup = $this->actingAs($this->admin)->post(route('school.transport.stops.store'), [
            'route_id' => $route->id,
            'stop_name' => 'Kohat Enclave',
            'stop_sequence' => 1, // Duplicate sequence!
        ]);
        $stopResDup->assertSessionHas('error');

        // CREATE STOP #2 (Valid)
        $this->actingAs($this->admin)->post(route('school.transport.stops.store'), [
            'route_id' => $route->id,
            'stop_name' => 'Kohat Enclave',
            'stop_sequence' => 2,
        ]);
        $this->assertDatabaseHas('transport_route_stops', ['stop_name' => 'Kohat Enclave', 'stop_sequence' => 2]);
    }

    /**
     * 3. Test Staff (Drivers/Attendants) CRUD & Reassignment.
     */
    public function test_drivers_and_staff_crud(): void
    {
        $vehicle = TransportVehicle::create([
            'vehicle_number' => 'DL-01-CD-5678',
            'vehicle_type' => 'Bus',
            'capacity' => 40,
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->admin)->post(route('school.transport.staff.store'), [
            'name' => 'Surender Singh',
            'role' => 'driver',
            'contact_number' => '9876543210',
            'license_number' => 'DL-2019-998877',
            'assigned_vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);
        $res->assertRedirect(route('school.transport.index', ['tab' => 'staff']));

        $driver = TransportStaff::where('name', 'Surender Singh')->first();
        $this->assertNotNull($driver);
        $this->assertEquals($vehicle->id, $driver->assigned_vehicle_id);

        // Vehicle should also have driver details synced
        $vehicle->refresh();
        $this->assertEquals($driver->id, $vehicle->driver_id);
        $this->assertEquals('Surender Singh', $vehicle->driver_name);
    }

    /**
     * 4. Test Student Transport Assignment, Duplicate Prevention & Bulk Assignment.
     */
    public function test_student_transport_assignment_and_bulk_operations(): void
    {
        $route = TransportRoute::create([
            'route_title' => 'East Expressway',
            'fare' => 1500.00,
            'status' => 'active',
        ]);

        $stop = TransportRouteStop::create([
            'route_id' => $route->id,
            'stop_name' => 'Anand Vihar',
            'stop_sequence' => 1,
            'pickup_time' => '07:20 AM',
        ]);

        $vehicle = TransportVehicle::create([
            'vehicle_number' => 'DL-01-EE-1234',
            'vehicle_type' => 'Bus',
            'capacity' => 40,
            'route_id' => $route->id,
            'status' => 'active',
        ]);

        // ASSIGN STUDENT 1
        $res = $this->actingAs($this->admin)->post(route('school.transport.students.assign'), [
            'student_id' => $this->student1->id,
            'route_id' => $route->id,
            'stop_id' => $stop->id,
            'waiver_percentage' => 10, // 10% waiver
            'discount_amount' => 50, // ₹50 flat discount
            'generate_invoice' => 1,
        ]);
        $res->assertRedirect(route('school.transport.index', ['tab' => 'students']));

        $assignment = StudentTransportAssignment::where('student_id', $this->student1->id)->where('status', 'active')->first();
        $this->assertNotNull($assignment);
        // Base: 1500, Waiver 10% = 150, Flat discount = 50 -> Effective Fare = 1300
        $this->assertEquals(1300.00, $assignment->effective_fare);

        // Auto-generated Fee Invoice verified (Category: transport, No fines)
        $invoice = FeeInvoice::where('student_id', $this->student1->id)->where('category', 'transport')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1300.00, (float) $invoice->total_due);
        $this->assertEquals(0.00, (float) $invoice->late_fine_amount);

        // PREVENT DUPLICATE ACTIVE ASSIGNMENT
        $dupRes = $this->actingAs($this->admin)->post(route('school.transport.students.assign'), [
            'student_id' => $this->student1->id,
            'route_id' => $route->id,
        ]);
        $dupRes->assertSessionHas('error');

        // BULK ASSIGN
        $bulkRes = $this->actingAs($this->admin)->post(route('school.transport.students.bulk_assign'), [
            'student_ids' => [$this->student1->id, $this->student2->id], // student1 will be skipped as duplicate
            'route_id' => $route->id,
            'stop_id' => $stop->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $bulkRes->assertRedirect(route('school.transport.index', ['tab' => 'students']));

        // Student 2 is now assigned
        $this->assertDatabaseHas('student_transport_assignments', [
            'student_id' => $this->student2->id,
            'route_id' => $route->id,
            'status' => 'active',
        ]);
    }

    /**
     * 5. Test Auto-assign Transport on Student Admission if Opted.
     */
    public function test_auto_assign_transport_on_admission(): void
    {
        $route = TransportRoute::create([
            'route_title' => 'South Ridge Express',
            'fare' => 2000.00,
            'status' => 'active',
        ]);

        $stop = TransportRouteStop::create([
            'route_id' => $route->id,
            'stop_name' => 'Saket Terminal',
            'stop_sequence' => 1,
        ]);

        $vehicle = TransportVehicle::create([
            'vehicle_number' => 'DL-01-EE-9999',
            'vehicle_type' => 'Bus',
            'capacity' => 40,
            'route_id' => $route->id,
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->admin)->post(route('school.students.store'), [
            'name' => 'Aditya Malhotra',
            'email' => 'aditya@student.academy',
            'class_id' => $this->class10A->id,
            'admission_number' => 'ADM-2026-0099',
            'roll_number' => 15,
            'guardian_name' => 'Ravi Malhotra',
            'guardian_phone' => '9811223344',
            'guardian_email' => 'ravi@malhotra.org',
            // Transport Opt-in fields
            'transport_opt_in' => 1,
            'transport_route_id' => $route->id,
            'transport_stop_id' => $stop->id,
            'transport_waiver_percentage' => 0,
            'transport_discount_amount' => 100, // Flat 100 discount -> 1900 effective
        ]);

        $newStudent = User::where('email', 'aditya@student.academy')->first();
        $this->assertNotNull($newStudent);

        // Verify active transport assignment created automatically
        $assignment = StudentTransportAssignment::where('student_id', $newStudent->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals($route->id, $assignment->route_id);
        $this->assertEquals(1900.00, $assignment->effective_fare);

        // Verify transport invoice created with no late fine
        $invoice = FeeInvoice::where('student_id', $newStudent->id)->where('category', 'transport')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1900.00, (float) $invoice->total_due);
        $this->assertEquals(0.00, (float) $invoice->late_fine_amount);
    }

    /**
     * 6. Test Batch Monthly Transport Fee Generation with Discount and No Fines.
     */
    public function test_generate_monthly_transport_fees_batch(): void
    {
        $route = TransportRoute::create([
            'route_title' => 'Metro Express',
            'fare' => 1600.00,
            'status' => 'active',
        ]);

        StudentTransportAssignment::create([
            'student_id' => $this->student1->id,
            'route_id' => $route->id,
            'status' => 'active',
            'monthly_fare' => 1600.00,
            'waiver_percentage' => 25.00, // 25% waiver -> 1200
        ]);

        $res = $this->actingAs($this->admin)->post(route('school.transport.fees.generate'), [
            'billing_month' => '2026-12',
            'route_id' => $route->id,
            'due_date' => '2026-12-15',
        ]);
        $res->assertRedirect();

        $invoice = FeeInvoice::where('student_id', $this->student1->id)
            ->where('category', 'transport')
            ->where('billing_month', '2026-12')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals(1200.00, (float) $invoice->total_due);
        $this->assertEquals(400.00, (float) $invoice->discount);
        $this->assertEquals(0.00, (float) $invoice->late_fine_amount);
    }

    /**
     * 7. Test Trip Dispatch Operations & Passenger Boarding Manifest.
     */
    public function test_live_trips_and_passenger_manifest(): void
    {
        $route = TransportRoute::create([
            'route_title' => 'City Link Corridor',
            'fare' => 1200.00,
            'status' => 'active',
        ]);

        $driver = TransportStaff::create([
            'name' => 'Surender Driver',
            'role' => 'driver',
            'contact_number' => '9811002233',
            'license_number' => 'DL-2026-LIC-999',
            'status' => 'active',
        ]);

        $vehicle = TransportVehicle::create([
            'vehicle_number' => 'DL-01-EE-7788',
            'vehicle_type' => 'Bus',
            'capacity' => 40,
            'route_id' => $route->id,
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'status' => 'active',
        ]);
        $driver->update(['assigned_vehicle_id' => $vehicle->id]);

        // Student assigned to this route
        StudentTransportAssignment::create([
            'student_id' => $this->student1->id,
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        // START TRIP
        $tripRes = $this->actingAs($this->admin)->post(route('school.transport.trips.store'), [
            'trip_date' => '2026-11-20',
            'trip_type' => 'pickup',
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $tripRes->assertRedirect(route('school.transport.index', ['tab' => 'trips']));

        $trip = TransportTrip::where('route_id', $route->id)->first();
        $this->assertNotNull($trip);
        $this->assertEquals('in_transit', $trip->status);

        // Manifest should automatically include student1
        $passenger = TransportTripPassenger::where('trip_id', $trip->id)->where('student_id', $this->student1->id)->first();
        $this->assertNotNull($passenger);
        $this->assertEquals('pending', $passenger->status);

        // BOARD PASSENGER
        $boardRes = $this->actingAs($this->admin)->post(route('school.transport.passengers.status', $passenger->id), [
            'status' => 'boarded',
        ]);
        $boardRes->assertRedirect();
        $passenger->refresh();
        $this->assertEquals('boarded', $passenger->status);

        // END TRIP
        $endRes = $this->actingAs($this->admin)->post(route('school.transport.trips.end', $trip->id));
        $endRes->assertRedirect();
        $trip->refresh();
        $this->assertEquals('completed', $trip->status);
    }

    /**
     * 8. Test Opt-Out Cancels Pending Uncollected Transport Invoices.
     */
    public function test_student_opt_out_cancels_uncollected_transport_fee_invoices(): void
    {
        $route = TransportRoute::create([
            'route_title' => 'Express North',
            'fare' => 1800.00,
            'status' => 'active',
        ]);

        $vehicle = TransportVehicle::create([
            'vehicle_number' => 'DL-01-EE-5555',
            'vehicle_type' => 'Bus',
            'capacity' => 30,
            'route_id' => $route->id,
            'status' => 'active',
        ]);

        // Assign student with invoice generation
        $res = $this->actingAs($this->admin)->post(route('school.transport.students.assign'), [
            'student_id' => $this->student1->id,
            'route_id' => $route->id,
            'monthly_fare' => 1800.00,
            'generate_invoice' => 1,
        ]);
        $res->assertRedirect(route('school.transport.index', ['tab' => 'students']));

        $assignment = StudentTransportAssignment::where('student_id', $this->student1->id)->where('status', 'active')->first();
        $this->assertNotNull($assignment);

        // Verify invoice was created
        $invoice = FeeInvoice::where('student_id', $this->student1->id)->where('category', 'transport')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('unpaid', $invoice->status);

        // Opt-out student
        $optOutRes = $this->actingAs($this->admin)->post(route('school.transport.students.opt_out', $assignment->id), [
            'reason' => 'Relocating to another area',
        ]);
        $optOutRes->assertRedirect();

        $assignment->refresh();
        $this->assertEquals('opted_out', $assignment->status);
        $this->assertEquals('Relocating to another area', $assignment->opt_out_reason);

        // Verify pending uncollected invoice was removed
        $remainingInvoice = FeeInvoice::where('student_id', $this->student1->id)->where('category', 'transport')->first();
        $this->assertNull($remainingInvoice);
    }
}
