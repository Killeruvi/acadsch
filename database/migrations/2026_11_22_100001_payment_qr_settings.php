<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add QR image route for settings
        // payment_qr_image stored in AcademySetting — no schema change needed.
        // Add cashier role support column tracking (the role field on users is a string — cashier already works).
        // Add uploadQrImage route — handled via routes/web.php.
        // This migration is a no-op schema-wise but marks the feature as applied.
    }

    public function down(): void {}
};
