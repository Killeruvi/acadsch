<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\PaymentGatewayOrder;
use App\Models\PaymentWebhookLog;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Ingest payment webhook notifications from Razorpay, Stripe, or UPI gateways.
     */
    public function handleWebhook(Request $request, string $gateway): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature')
            ?? $request->header('Stripe-Signature')
            ?? $request->input('signature')
            ?? '';

        $isVerified = PaymentGatewayService::verifySignature($gateway, $rawPayload, $signature);

        $payload = $request->json()->all() ?: $request->all();

        // Extract order reference and payment reference depending on gateway
        $orderId = null;
        $paymentId = null;

        if ($gateway === 'razorpay') {
            $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
            $orderId = $paymentEntity['order_id'] ?? $payload['order_id'] ?? null;
            $paymentId = $paymentEntity['id'] ?? $payload['payment_id'] ?? null;
        } elseif ($gateway === 'stripe') {
            $orderId = $payload['data']['object']['metadata']['order_reference'] ?? null;
            $paymentId = $payload['data']['object']['id'] ?? null;
        } else {
            $orderId = $payload['order_reference'] ?? $payload['order_id'] ?? null;
            $paymentId = $payload['payment_id'] ?? $payload['transaction_id'] ?? null;
        }

        if (! $orderId || ! $paymentId) {
            PaymentWebhookLog::create([
                'gateway' => $gateway,
                'event' => $payload['event'] ?? 'unknown',
                'transaction_reference' => $paymentId ?? 'none',
                'status' => 'failed',
                'signature_verified' => $isVerified,
                'payload' => $payload,
                'response_status' => 400,
                'response_message' => 'Missing order or payment reference in webhook payload.',
            ]);

            return response()->json(['status' => 'error', 'message' => 'Incomplete webhook identifiers.'], 400);
        }

        // Reconcile payment into school ledger
        $result = PaymentGatewayService::reconcilePayment($orderId, $paymentId, $payload);

        return response()->json([
            'status' => 'acknowledged',
            'gateway' => $gateway,
            'result' => $result,
        ]);
    }

    /**
     * Initiate online payment checkout for an unpaid school fee invoice.
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:fee_invoices,id',
            'gateway' => 'nullable|string|in:razorpay,stripe,upi_intent',
        ]);

        $invoice = FeeInvoice::findOrFail($validated['invoice_id']);
        $order = PaymentGatewayService::createOrder($invoice, $validated['gateway'] ?? 'razorpay');
        $config = PaymentGatewayService::getConfig();

        return response()->json([
            'status' => 'success',
            'order_reference' => $order->order_reference,
            'gateway_order_id' => $order->gateway_order_id,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'gateway' => $order->gateway,
            'razorpay_key_id' => $config['razorpay_key_id'],
            'student_name' => $invoice->student?->name,
            'invoice_number' => $invoice->invoice_number,
        ]);
    }
}
