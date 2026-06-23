# QR Code Payment API - Quick Start Guide

## 🚀 Quick Overview

Process payments via QR code scan - works just like POS card payments but with QR data!

---

## 📋 What Was Created

### Files Created:
1. ✅ **Controller**: `app/Http/Controllers/Api/V3/QrCodePaymentController.php`
2. ✅ **Request Validator**: `app/Http/Requests/Api/V3/QrPaymentRequest.php`
3. ✅ **Service Methods**: Added to `app/Services/TransactionService.php`:
   - `processQrPayment()`
   - `checkForDuplicateQrTransaction()`
   - `simulateQrPaymentGateway()`
   - `getQrTransactionsFiltered()`
   - `getQrTransactionById()`
4. ✅ **Routes**: Added to `routes/api.php` (V3 API section)
5. ✅ **Postman Collection**: `QR_Payment_API_V3_Postman_Collection.json`
6. ✅ **Documentation**: `QR_PAYMENT_API_DOCUMENTATION.md`

---

## 🔑 API Endpoints

### 1. Process QR Payment
```
POST /api/v3/qr-payment
POST /api/v3/qr-payment/process (alternative)
```

### 2. Get QR Transactions
```
GET /api/v3/qr-payment/transactions
```

### 3. Get Single QR Transaction
```
GET /api/v3/qr-payment/transactions/{id}
```

---

## 📝 Minimal Example Request

```json
POST /api/v3/qr-payment

Headers:
X-API-Key: your-api-key-here
Content-Type: application/json

Body:
{
    "transactionDetails": {
        "amount": 50.00,
        "currency": "USD",
        "timestamp": "2026-01-12T14:30:00Z",
        "transactionType": "SALE"
    },
    "qrData": {
        "qrCode": "QR123456789ABCDEF",
        "qrType": "DYNAMIC"
    },
    "merchantDetails": {
        "merchantId": "1",
        "terminalId": "1"
    }
}
```

---

## 📝 Full Example Request

```json
{
    "transactionDetails": {
        "amount": 150.00,
        "currency": "USD",
        "timestamp": "2026-01-12T14:30:00Z",
        "transactionType": "SALE"
    },
    "qrData": {
        "qrCode": "QR123456789ABCDEF",
        "qrType": "DYNAMIC",
        "customerName": "John Doe",
        "customerId": "CUST12345",
        "phoneNumber": "+1234567890",
        "email": "john.doe@example.com",
        "walletProvider": "PayWallet"
    },
    "merchantDetails": {
        "merchantId": "1",
        "terminalId": "1",
        "branchId": "1"
    },
    "additionalData": {
        "description": "Payment for order #12345",
        "orderNumber": "ORD-12345",
        "notes": "Express delivery"
    }
}
```

---

## ✅ Success Response

```json
{
    "status": true,
    "data": {
        "message": "QR payment processed successfully",
        "transaction": {
            "id": "uuid-here",
            "transaction_id": "QR-20260112-ABCD1234",
            "amount": 150.00,
            "currency": "USD",
            "status": "APPROVED",
            "payment_method": "qr_code",
            "qr_type": "DYNAMIC",
            "customer_name": "John Doe",
            "wallet_provider": "PayWallet",
            "invoice_no": "QR-INV-20260112-ABCD1234",
            "created_at": "2026-01-12T14:30:00Z"
        },
        "qr_response": {
            "status": "APPROVED",
            "gateway_transaction_id": "QRG-ABCDEF1234567890",
            "gateway_response_code": "00",
            "gateway_response_message": "QR payment approved"
        }
    }
}
```

---

## 🎯 Key Features

- ✅ **Same Structure as Card Payments**: Easy to understand if you know the POS API
- ✅ **Two QR Types**: STATIC (reusable) and DYNAMIC (one-time)
- ✅ **Multiple Wallets**: Support for various wallet providers
- ✅ **Duplicate Detection**: Prevents duplicate transactions within 1 minute
- ✅ **Webhooks**: Real-time notifications (transaction.created, qr_payment.succeeded, etc.)
- ✅ **Multi-Currency**: Support for any currency
- ✅ **Batch Management**: Transactions grouped in batches like POS payments
- ✅ **Full Audit Trail**: Complete transaction history

---

## 🔐 Authentication

All requests require API Key authentication:

```
Header: X-API-Key: your-api-key-here
```

---

## 🧪 Testing with Postman

1. Import `QR_Payment_API_V3_Postman_Collection.json` into Postman
2. Set variables:
   - `base_url`: http://localhost:8000 (or your server URL)
   - `api_key`: your-api-key-here
3. Run the "Process QR Payment" request
4. Check the response!

---

## 📊 Database Fields Added

The QR payment adds these fields to the `transactions` table:

- `payment_method` = 'qr_code'
- `qr_code` - The scanned QR code data
- `qr_type` - STATIC or DYNAMIC
- `customer_name` - Customer name from QR
- `customer_id` - Customer ID from QR
- `customer_phone` - Customer phone number
- `customer_email` - Customer email
- `wallet_provider` - Wallet/payment provider name
- `order_number` - Order reference
- `notes` - Additional notes

---

## 🔄 How It Works

1. **Scan QR Code**: Terminal scans customer's QR code
2. **Extract Data**: Parse QR code data (amount, customer info, etc.)
3. **Call API**: Send POST request to `/api/v3/qr-payment`
4. **Process Payment**: System processes through payment gateway (simulated for now)
5. **Return Result**: Get transaction status (APPROVED/DECLINED)
6. **Webhooks Fired**: Real-time notifications sent to your webhook endpoint

---

## 🎨 QR Code Types Explained

### STATIC QR Code
- ✅ Reusable QR code
- ✅ Displayed at merchant location
- ✅ Customer scans to initiate payment
- ✅ Amount entered at terminal or predetermined
- 📱 Example: QR code sticker at store counter

### DYNAMIC QR Code
- ✅ One-time use only
- ✅ Generated per transaction
- ✅ Contains transaction-specific data
- ✅ More secure
- 📱 Example: QR code in payment app for specific invoice

---

## 🔔 Webhook Events

When a QR payment is processed, these webhooks are triggered:

1. `transaction.created` - Always fired
2. `transaction.approved` - If approved
3. `payment.succeeded` - If approved
4. `qr_payment.succeeded` - QR-specific success event
5. `transaction.declined` - If declined
6. `transaction.failed` - If failed

---

## 🛠️ Next Steps

### For Production:

1. **Replace Simulation**: Update `simulateQrPaymentGateway()` in TransactionService with actual gateway integration
2. **Add Real Gateway**: Integrate with your QR payment gateway (e.g., Alipay, WeChat Pay, etc.)
3. **Database Migration**: Ensure `transactions` table has all QR-related columns
4. **Configure Webhooks**: Set up webhook endpoints for real-time updates
5. **Test Thoroughly**: Test with real QR codes and payment providers

### Database Migration Needed:

```php
Schema::table('transactions', function (Blueprint $table) {
    $table->string('qr_code')->nullable();
    $table->enum('qr_type', ['STATIC', 'DYNAMIC'])->nullable();
    $table->string('customer_name')->nullable();
    $table->string('customer_id')->nullable();
    $table->string('customer_phone')->nullable();
    $table->string('customer_email')->nullable();
    $table->string('wallet_provider')->nullable();
    $table->string('order_number')->nullable();
    $table->text('notes')->nullable();
});
```

---

## 📚 Documentation Files

- **Full Documentation**: `QR_PAYMENT_API_DOCUMENTATION.md`
- **Postman Collection**: `QR_Payment_API_V3_Postman_Collection.json`
- **This Guide**: `QR_PAYMENT_QUICK_START.md`

---

## 🐛 Common Issues & Solutions

### Issue: "No active terminal session"
**Solution**: Ensure terminal is registered and active, and terminalId is correct in request.

### Issue: "Duplicate transaction detected"
**Solution**: Wait 1 minute before retrying same QR code with same amount, or use different QR code.

### Issue: "Validation failed"
**Solution**: Check all required fields are present and properly formatted.

### Issue: "Transaction declined"
**Solution**: Check decline_reason in response. May be insufficient balance, expired QR, etc.

---

## 💡 Tips

1. **Test First**: Use Postman collection to test before integrating
2. **Error Handling**: Always handle errors gracefully in your app
3. **Log Everything**: Log all requests/responses for debugging
4. **Use Webhooks**: Don't poll for status - use webhooks instead
5. **Secure API Keys**: Never expose API keys in client-side code

---

## 🎉 You're Ready!

You now have a fully functional QR Code Payment API that works just like your POS card payment API!

**Happy Coding! 🚀**












