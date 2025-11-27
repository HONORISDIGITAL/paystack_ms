# Paystack Webhook Postman Collection

This Postman collection allows you to test Paystack webhook endpoints with the exact payload structure from actual Paystack webhook events.

## 📋 Collection Contents

The collection includes three test requests:

1. **Payment Webhook - charge.success** - Successful payment webhook (exact payload from logs)
2. **Payment Webhook - charge.failed** - Failed payment webhook
3. **Payment Webhook - charge.pending** - Pending payment webhook

## 🚀 Setup Instructions

### 1. Import the Collection

1. Open Postman
2. Click **Import** button
3. Select the file: `Paystack_Webhook_Test.postman_collection.json`
4. The collection will be imported with all requests

### 2. Configure Environment Variables

You need to set up environment variables for the collection to work properly:

#### Option A: Create a Postman Environment

1. Click the **Environments** icon (left sidebar)
2. Click **+** to create a new environment
3. Name it "Paystack Local" or "Paystack Production"
4. Add these variables:

| Variable | Initial Value | Current Value | Description |
|----------|--------------|---------------|-------------|
| `base_url` | `http://paystack.test:8081` | `http://paystack.test:8081` | Your local API base URL |
| `PAYSTACK_SECRET_KEY` | `sk_test_...` | `sk_test_...` | Your Paystack secret key |

5. Select the environment from the dropdown (top right)

#### Option B: Use Collection Variables

1. Right-click on the collection
2. Select **Edit**
3. Go to the **Variables** tab
4. Set:
   - `base_url`: `http://paystack.test:8081` (or your API URL)
   - `paystack_secret_key`: Your Paystack secret key

### 3. Update Base URL

The default base URL is set to `http://paystack.test:8081`. Update it to match your setup:

- **Local**: `http://paystack.test:8081` or `http://localhost:8081`
- **Production**: `https://your-domain.com`

## 🔐 Signature Calculation

The collection automatically calculates the `x-paystack-signature` header using a **pre-request script**. This script:

1. Reads your Paystack secret key from environment/collection variables
2. Gets the raw request body
3. Calculates HMAC SHA512 signature (same as Paystack does)
4. Adds the signature to the `x-paystack-signature` header

**Important**: The signature is calculated automatically, so you don't need to manually set it. Just make sure your `PAYSTACK_SECRET_KEY` is set correctly.

## 📝 Request Details

### Endpoint
```
POST {{base_url}}/api/webhooks/paystack/payment
```

### Headers
- `Content-Type: application/json`
- `User-Agent: Paystack/2.0`
- `x-paystack-signature: <auto-calculated>`

### Payload Structure

The payload matches the exact structure from Paystack webhook logs:

```json
{
  "event": "charge.success",
  "data": {
    "id": 5549185396,
    "domain": "test",
    "status": "success",
    "reference": "920556089538-ecampus-1763544356703",
    "amount": 33390000,
    "gateway_response": "Successful",
    "paid_at": "2025-11-19T09:26:05.000Z",
    "created_at": "2025-11-19T09:26:00.000Z",
    "channel": "card",
    "currency": "NGN",
    "authorization": { ... },
    "customer": { ... },
    "source": { ... },
    ...
  }
}
```

## 🧪 Testing

1. **Select the request** you want to test (e.g., "Payment Webhook - charge.success")
2. **Make sure your environment is selected** (top right dropdown)
3. **Click Send**
4. **Check the response** - You should get a 200 OK response with:
   ```json
   {
     "status": "success",
     "message": "Webhook received and processed",
     "webhook_id": "...",
     "timestamp": "..."
   }
   ```

## 🔍 Debugging

### Check Signature Calculation

1. Open the **Console** in Postman (bottom panel or View → Show Postman Console)
2. Send a request
3. Look for: `Calculated signature: <signature>`
4. Compare with the signature validation logs in your Laravel application

### Verify Payload

The payload in the collection matches the exact structure from your logs. You can modify it to test different scenarios:

- Change `event` type (charge.success, charge.failed, charge.pending)
- Modify `status` field
- Update `amount` values
- Change customer/authorization data

## 📊 Expected Response

On success, you should receive:

```json
{
  "status": "success",
  "message": "Webhook received and processed",
  "webhook_id": "webhook_...",
  "timestamp": "2025-11-19T..."
}
```

## ⚠️ Troubleshooting

### Signature Validation Failing

1. **Check your secret key**: Make sure `PAYSTACK_SECRET_KEY` matches your `.env` file
2. **Verify body format**: The signature is calculated from the raw JSON body (exact string)
3. **Check console logs**: Look at Postman console for calculated signature
4. **Check Laravel logs**: Look for signature validation debug messages

### Connection Errors

1. **Verify base_url**: Make sure it matches your local/production URL
2. **Check server is running**: Ensure your Laravel application is running
3. **Verify endpoint**: Confirm the route exists: `/api/webhooks/paystack/payment`

### Payload Not Processing

1. **Check Laravel logs**: Look for webhook processing errors
2. **Verify database**: Make sure migrations have been run
3. **Check validation**: Look for validation errors in logs

## 📚 Additional Resources

- [Paystack Webhook Documentation](https://paystack.com/docs/payments/webhooks/)
- [Postman Documentation](https://learning.postman.com/docs/)

## 🎯 Next Steps

After testing with this collection, you can:

1. **Modify payloads** to test different payment scenarios
2. **Add more requests** for other webhook events (transfer, subscription, etc.)
3. **Create test scripts** in Postman to validate responses
4. **Set up automated tests** using Postman's test runner

---

**Note**: This collection uses the exact payload structure from your actual Paystack webhook logs, ensuring accurate testing of your webhook handler.



