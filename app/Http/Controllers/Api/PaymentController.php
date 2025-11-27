<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\DTOs\PaystackPaymentDTO;

class PaymentController extends Controller
{
    /**
     * Create or update a payment (from Nexus)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment' => 'required|array',
            'payment.transaction_id' => 'required|string',
            'payment.amount' => 'nullable|numeric',
            'payment.currency' => 'nullable|string',
            'payment.status' => 'required|string',
            'payment.payment_type' => 'nullable|string',
            'payment.payment_page' => 'nullable|string',
            'payment.paymentPage' => 'nullable|string',
            'payment.price' => 'nullable|string', // Changed to string to handle comma-separated values
            'payment.transaction_reference' => 'nullable|string',
            'payment.reference' => 'nullable|string',
        ]);

        $data = $validated['payment'];

        // Handle both paymentPage and payment_page field names
        if (isset($data['paymentPage']) && !isset($data['payment_page'])) {
            $data['payment_page'] = $data['paymentPage'];
        }
        unset($data['paymentPage']); // Remove the camelCase version

        // Format price field - remove commas and convert to decimal
        if (isset($data['price']) && !empty($data['price'])) {
            $price = $data['price'];
            // Remove commas and convert to float
            $formattedPrice = (float) str_replace(',', '', $price);
            $data['price'] = $formattedPrice;
            
            Log::info('Formatted price field in Paystack', [
                'original_price' => $price,
                'formatted_price' => $formattedPrice
            ]);
        }

        // If transaction_reference is provided (from Nexus), map it to reference
        if (!empty($data['transaction_reference']) && empty($data['reference'])) {
            $data['reference'] = $data['transaction_reference'];
        }

        Log::info('🔄 [PS-CONTROLLER] Received payment create request from Nexus', [
            'reference' => $data['reference'] ?? $data['transaction_reference'] ?? 'unknown',
            'transaction_id' => $data['transaction_id'] ?? 'unknown',
            'data_keys' => array_keys($data)
        ]);

        try {
            // Create PaystackPaymentDTO from incoming Nexus data
            Log::info('🔄 [PS-CONTROLLER] Creating PaystackPaymentDTO from Nexus data', [
                'transaction_id' => $data['transaction_id'] ?? 'unknown'
            ]);

            $paystackPaymentDTO = PaystackPaymentDTO::fromArray($data);

            Log::info('✅ [PS-CONTROLLER] PaystackPaymentDTO created from Nexus data', [
                'transaction_id' => $paystackPaymentDTO->transactionId,
                'reference' => $paystackPaymentDTO->reference
            ]);

            // Get data from DTO for database operations
            $saveData = $paystackPaymentDTO->toArray();

            // Merge any additional Paystack-specific fields that might come from Nexus
            // but aren't in the DTO (like payment_page, price, etc.)
            if (isset($data['payment_page'])) {
                $saveData['payment_page'] = $data['payment_page'];
            }
            if (isset($data['price'])) {
                $saveData['price'] = $data['price'];
            }

            Log::info('💾 [PS-CONTROLLER] Saving payment using DTO data', [
                'transaction_id' => $paystackPaymentDTO->transactionId,
                'reference' => $paystackPaymentDTO->reference,
                'data_keys' => array_keys($saveData)
            ]);

            // Check by reference first, then transaction_id
            $existing = null;
            if ($paystackPaymentDTO->reference) {
                $existing = Payment::where('reference', $paystackPaymentDTO->reference)->first();
            }
            if (!$existing) {
                $existing = Payment::where('transaction_id', $paystackPaymentDTO->transactionId)->first();
            }

            if ($existing) {
                $existing->update($saveData);
                Log::info('✅ [PS-CONTROLLER] Payment updated successfully using DTO', [
                    'payment_id' => $existing->id,
                    'reference' => $paystackPaymentDTO->reference,
                    'transaction_id' => $paystackPaymentDTO->transactionId
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment updated',
                    'payment_id' => $existing->id,
                ]);
            }

            $payment = Payment::create($saveData);
            Log::info('✅ [PS-CONTROLLER] Payment created successfully using DTO', [
                'payment_id' => $payment->id,
                'reference' => $paystackPaymentDTO->reference,
                'transaction_id' => $paystackPaymentDTO->transactionId
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment created',
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [PS-CONTROLLER] Payment creation/update failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment creation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}


