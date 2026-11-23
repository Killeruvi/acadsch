<?php

namespace App\Services;

use App\Models\AcademySetting;

/**
 * PaymentGatewayService — DISABLED
 * This ERP uses QR-only payments; no payment gateway is integrated.
 * All collections are recorded manually by the Cashier or Admin.
 */
class PaymentGatewayService
{
    public static function getConfig(): array
    {
        $settings = AcademySetting::pluck('value', 'key')->toArray();
        return [
            'mode'                 => 'manual',
            'active_gateway'       => 'none',
            'auto_receipt_generate'=> (bool) ($settings['auto_receipt_generate'] ?? true),
            'currency_code'        => $settings['currency_code'] ?? 'INR',
            'currency_symbol'      => $settings['currency_symbol'] ?? '₹',
            'payment_modes'        => ['cash', 'upi', 'qr', 'bank', 'cheque'],
        ];
    }

    public static function getEnabledGateways(): array
    {
        return [];
    }

    public static function testConnection(string $gateway = 'none', array $params = []): array
    {
        return [
            'success' => false,
            'message' => 'No payment gateway is configured. This school uses QR + cashier manual payment recording.',
        ];
    }
}
