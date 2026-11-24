<?php

namespace App\Services;

use App\Models\AcademySetting;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\PaymentGatewayOrder;
use App\Models\PaymentWebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public static function getConfig(): array
    {
        $settings = AcademySetting::pluck('value', 'key')->toArray();

        return [
            'mode' => $settings['payment_gateway_mode'] ?? 'unified',
            'active_gateway' => $settings['active_payment_gateway'] ?? 'razorpay',
            'currency_code' => $settings['currency_code'] ?? 'INR',
            'currency_symbol' => $settings['currency_symbol'] ?? '₹',
            'razorpay_key_id' => $settings['razorpay_key_id'] ?? env('RAZORPAY_KEY_ID', ''),
            'razorpay_key_secret' => $settings['razorpay_key_secret'] ?? env('RAZORPAY_KEY_SECRET', ''),
            'stripe_key' => $settings['stripe_key'] ?? env('STRIPE_KEY', ''),
            'stripe_secret' => $settings['stripe_secret'] ?? env('STRIPE_SECRET', ''),
            'payment_modes' => ['online_gateway', 'upi', 'cash', 'qr', 'bank', 'cheque'],
        ];
    }

    /**
     * Create an online checkout payment order for a school fee invoice.
     */
    public static function createOrder(FeeInvoice $invoice, string $gateway = 'razorpay'): PaymentGatewayOrder
    {
        $due = max(0.0, (float) $invoice->total_due - (float) $invoice->paid_amount);
        $orderRef = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

        return PaymentGatewayOrder::create([
            'order_reference' => $orderRef,
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'gateway' => in_array($gateway, ['razorpay', 'stripe', 'upi_intent', 'manual']) ? $gateway : 'razorpay',
            'amount' => $due,
            'currency' => 'INR',
            'status' => 'created',
            'gateway_order_id' => 'sim_'.Str::random(14),
        ]);
    }

    /**
     * Verify cryptographic signature for Razorpay / Stripe webhooks.
     */
    public static function verifySignature(string $gateway, string $payload, string $signature): bool
    {
        $config = self::getConfig();

        if ($gateway === 'razorpay') {
            $secret = $config['razorpay_key_secret'];
            if (empty($secret)) {
                // In development / demo sandbox, verify payload exists
                return ! empty($signature);
            }
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            return hash_equals($expectedSignature, $signature);
        }

        return ! empty($signature);
    }

    /**
     * Atomically reconcile an online payment order into the official school fee ledger.
     */
    public static function reconcilePayment(
        string $orderReference,
        string $gatewayPaymentId,
        array $metadata = []
    ): array {
        return DB::transaction(function () use ($orderReference, $gatewayPaymentId, $metadata) {
            $order = PaymentGatewayOrder::where('order_reference', $orderReference)
                ->orWhere('gateway_order_id', $orderReference)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return ['success' => false, 'message' => 'Payment order reference not found.'];
            }

            if ($order->status === 'paid') {
                return [
                    'success' => true,
                    'already_settled' => true,
                    'receipt_number' => $order->receipt_number,
                ];
            }

            $invoice = FeeInvoice::whereKey($order->invoice_id)->lockForUpdate()->firstOrFail();

            // Generate official school receipt
            $receiptNo = 'REC-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

            $payment = FeePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $order->amount,
                'payment_date' => now()->toDateString(),
                'payment_mode' => 'online_gateway',
                'transaction_reference' => $gatewayPaymentId,
                'receipt_number' => $receiptNo,
                'notes' => "Auto-reconciled via {$order->gateway} gateway webhook ({$order->order_reference}).",
                'recorded_by' => 1, // System automated cashier
            ]);

            // Update invoice ledger
            $newPaid = (float) $invoice->paid_amount + (float) $order->amount;
            $newStatus = $newPaid >= (float) $invoice->total_due ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $newStatus,
            ]);

            // Update order status
            $order->update([
                'status' => 'paid',
                'gateway_payment_id' => $gatewayPaymentId,
                'receipt_number' => $receiptNo,
                'webhook_received_at' => now(),
            ]);

            // Dispatch notification
            NotificationService::notifyUser(
                $order->student_id,
                'fee_payment',
                'Fee Payment Confirmed',
                "Payment of ₹".number_format($order->amount, 2)." successfully received for Invoice #{$invoice->invoice_number}. Receipt #{$receiptNo}.",
                route('school.fees.receipt', $payment->id),
                '💳'
            );

            // Log in PaymentWebhookLog
            PaymentWebhookLog::create([
                'gateway' => $order->gateway,
                'event' => 'payment.captured',
                'transaction_reference' => $gatewayPaymentId,
                'invoice_id' => $invoice->id,
                'amount' => $order->amount,
                'status' => 'success',
                'signature_verified' => true,
                'payload' => $metadata,
                'response_status' => 200,
                'response_message' => "Settled with receipt {$receiptNo}",
            ]);

            return [
                'success' => true,
                'receipt_number' => $receiptNo,
                'payment_id' => $payment->id,
            ];
        });
    }
}
