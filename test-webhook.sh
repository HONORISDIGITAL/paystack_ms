#!/bin/bash

# Test webhook for Paystack project
echo "🧪 Testing Paystack webhook with queue system..."

# Send test webhook
curl -X POST http://paystack.test:8081/api/webhooks/paystack/payment \
  -H "Content-Type: application/json" \
  -H "User-Agent: Paystack-Webhook/1.0" \
  -H "x-paystack-signature: test_signature_12345" \
  -d '{
    "event": "charge.success",
    "data": {
      "id": 1234567891,
      "reference": "PSK-TEST-QUEUE-' $(date +%s) '",
      "amount": 250000,
      "currency": "NGN",
      "status": "success",
      "channel": "card",
      "gateway_response": "Successful",
      "message": "Approved",
      "created_at": "' $(date -u +%Y-%m-%dT%H:%M:%S.%3NZ) '",
      "ip_address": "127.0.0.1",
      "customer": {
        "id": 123457,
        "first_name": "Queue",
        "last_name": "Test User",
        "email": "queue.test@example.com",
        "phone": "+2348012345679"
      },
      "authorization": {
        "bin": "123456",
        "last4": "7891",
        "bank": "MASTERCARD",
        "country_code": "NG",
        "card_type": "DEBIT",
        "exp_month": "09",
        "exp_year": "25",
        "channel": "card"
      },
      "metadata": {
        "source": "queue_test",
        "test_mode": true,
        "paymentPage": "test-page",
        "price": "2500"
      }
    }
  }'

echo ""
echo "✅ Webhook sent! Now checking queue status..."

# Wait a moment for processing
sleep 2

# Check queue status
echo "📊 Queue Status:"
cd /Users/zanesmith/Sites/Honoris/paystack_ms && php artisan queue:monitor --limit=5





