<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTransformationAndPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully_with_brand_identity(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('PrimeOS');
        $response->assertSee('Your Potential Has');
        $response->assertSee('No Fixed Limits.');
        $response->assertSee('Education Should Create Capability');
        $response->assertSee('hero-students-learning.jpg');
    }

    public function test_about_us_page_loads_with_required_story_and_values(): void
    {
        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertSee('About Us');
        $response->assertSee('Our Vision');
        $response->assertSee('Our Mission');
        $response->assertSee('Our Core Values');
        $response->assertSee('Knowledge');
        $response->assertSee('Mastery');
        $response->assertSee('Curiosity');
        $response->assertSee('Innovation');
        $response->assertSee('Integrity');
        $response->assertSee('Accessibility');
        $response->assertSee('Growth');
        $response->assertSee('We are not here merely to help students complete a syllabus.');
    }

    public function test_privacy_policy_page_loads_with_student_protection(): void
    {
        $response = $this->get('/privacy-policy');
        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('Protection of Minors');
        $response->assertSee('Technical Safeguards');
    }

    public function test_terms_and_conditions_page_loads_with_institutional_code(): void
    {
        $response = $this->get('/terms');
        $response->assertStatus(200);
        $response->assertSee('Terms & Conditions');
        $response->assertSee('Academic Integrity');
        $response->assertSee('Faculty Authorization & Device Restrictions');
    }

    public function test_admissions_page_loads_with_structured_steps(): void
    {
        $response = $this->get('/admissions');
        $response->assertStatus(200);
        $response->assertSee('How to Enroll');
        $response->assertSee('Select Academic Class');
        $response->assertSee('Choose Preferred Shift');
    }

    public function test_contact_page_loads_and_handles_inquiry_submission(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Send an Inquiry');

        $postResponse = $this->post('/contact', [
            'name' => 'Prithviraj Parent',
            'email' => 'parent@example.com',
            'phone' => '+91 98765 43210',
            'inquiry_type' => 'admissions',
            'message' => 'Inquiring about Class 10 morning shift admissions.',
        ]);

        $postResponse->assertSessionHas('success');
    }

    public function test_courses_catalog_is_accessible_to_guests(): void
    {
        $response = $this->get('/courses');
        $response->assertStatus(200);
        $response->assertSee('Courses Catalog');
    }
}
