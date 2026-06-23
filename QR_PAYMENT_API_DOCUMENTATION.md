# QR Code Payment API V3 Documentation

## Overview

The QR Code Payment API V3 enables merchants to process payments by scanning QR codes from customer wallets or payment apps. This API follows the same structure and flow as the standard POS card payment API but uses QR code data instead of card data.

## Key Features

- ✅ Process payments via QR code scan (STATIC or DYNAMIC QR codes)
- ✅ Same structure as POS card payments for consistency
- ✅ Support for multiple wallet providers
- ✅ Real-time transaction processing
- ✅ Webhook notifications for transaction events
- ✅ Complete transaction history and reporting
- ✅ Duplicate transaction detection
- ✅ Multi-currency support

## Authentication

All API requests must include an API Key in the header:

```
X-API-Key: your-api-key-here
```

## Base URL

```
Production: https://your-domain.com/api/v3
Development: http://localhost:8000/api/v3
```

---

## API Endpoints

### 1. Process QR Payment

Process a payment transaction via QR code scan.

**Endpoint:** `POST /api/v3/qr-payment`  
**Alternative:** `POST /api/v3/qr-payment/process`

#### Request Body

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
        "notes": "Express delivery requested"
    }
}
```

#### Request Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `transactionDetails` | object | Yes | Transaction information |
| `transactionDetails.amount` | number | Yes | Transaction amount (min: 0.01) |
| `transactionDetails.currency` | string | Yes | Currency code (e.g., USD, EUR) |
| `transactionDetails.timestamp` | datetime | Yes | Transaction timestamp (ISO 8601) |
| `transactionDetails.transactionType` | string | Yes | Type: SALE, REFUND, VOID |
| `qrData` | object | Yes | QR code information |
| `qrData.qrCode` | string | Yes | Scanned QR code data (max: 500 chars) |
| `qrData.qrType` | string | Yes | QR type: STATIC or DYNAMIC |
| `qrData.customerName` | string | No | Customer name from QR |
| `qrData.customerId` | string | No | Customer ID from QR |
| `qrData.phoneNumber` | string | No | Customer phone number |
| `qrData.email` | string | No | Customer email |
| `qrData.walletProvider` | string | No | Wallet/payment provider name |
| `merchantDetails` | object | Yes | Merchant information |
| `merchantDetails.merchantId` | string | Yes | Merchant ID |
| `merchantDetails.terminalId` | string | Yes | Terminal ID |
| `merchantDetails.branchId` | string | No | Branch ID |
| `additionalData` | object | No | Additional transaction data |
| `additionalData.description` | string | No | Payment description (max: 500) |
| `additionalData.orderNumber` | string | No | Order number (max: 100) |
| `additionalData.notes` | string | No | Additional notes (max: 1000) |

#### Success Response (201 Created)

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
            "transaction_type": "SALE",
            "payment_method": "qr_code",
            "qr_type": "DYNAMIC",
            "customer_name": "John Doe",
            "wallet_provider": "PayWallet",
            "invoice_no": "QR-INV-20260112-ABCD1234",
            "rrn": "260112543938",
            "auth_code": "123456",
            "trace_no": "543938",
            "batch_no": "001",
            "created_at": "2026-01-12T14:30:00Z"
        },
        "qr_response": {
            "status": "APPROVED",
            "gateway_transaction_id": "QRG-ABCDEF1234567890",
            "gateway_response_code": "00",
            "gateway_response_message": "QR payment approved",
            "wallet_provider": "PayWallet",
            "qr_type": "DYNAMIC",
            "processed_at": "2026-01-12T14:30:00Z"
        }
    }
}
```

#### Error Responses

**404 Not Found - Terminal Not Found**
```json
{
    "status": false,
    "message": "No active terminal session",
    "Error_Code": "TERMINAL_NOT_FOUND"
}
```

**409 Conflict - Duplicate Transaction**
```json
{
    "status": false,
    "message": "Duplicate transaction detected. A QR payment with the same amount and QR code was processed within the last minute.",
    "Error_Code": "DUPLICATE_TRANSACTION"
}
```

**422 Unprocessable Entity - Validation Error**
```json
{
    "status": false,
    "message": "Validation failed",
    "errors": {
        "transactionDetails.amount": ["Transaction amount is required"],
        "qrData.qrCode": ["QR code is required"]
    },
    "Error_Code": "VALIDATION_ERROR"
}
```

**500 Internal Server Error**
```json
{
    "status": false,
    "message": "Failed to process QR payment",
    "Error_Code": "QR_PAYMENT_ERROR"
}
```

---

### 2. Get All QR Transactions

Retrieve a paginated list of QR code payment transactions.

**Endpoint:** `GET /api/v3/qr-payment/transactions`

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 10, max: 100) |
| `status` | string | No | Filter by status: PENDING, APPROVED, DECLINED, FAILED |
| `qr_type` | string | No | Filter by QR type: STATIC, DYNAMIC |
| `search` | string | No | Search by transaction_id, qr_code, customer_name, invoice_no |
| `start_date` | date | No | Filter from date (YYYY-MM-DD) |
| `end_date` | date | No | Filter to date (YYYY-MM-DD) |

#### Example Request

```
GET /api/v3/qr-payment/transactions?page=1&per_page=10&status=APPROVED&qr_type=DYNAMIC
```

#### Success Response (200 OK)

```json
{
    "status": true,
    "data": {
        "transactions": {
            "current_page": 1,
            "data": [
                {
                    "id": "uuid-1",
                    "transaction_id": "QR-20260112-ABCD1234",
                    "amount": 150.00,
                    "currency": "USD",
                    "status": "APPROVED",
                    "payment_method": "qr_code",
                    "qr_type": "DYNAMIC",
                    "customer_name": "John Doe",
                    "wallet_provider": "PayWallet",
                    "created_at": "2026-01-12T14:30:00Z"
                }
            ],
            "first_page_url": "...",
            "from": 1,
            "last_page": 5,
            "last_page_url": "...",
            "next_page_url": "...",
            "path": "...",
            "per_page": 10,
            "prev_page_url": null,
            "to": 10,
            "total": 50
        }
    }
}
```

---

### 3. Get QR Transaction by ID

Retrieve details of a specific QR payment transaction.

**Endpoint:** `GET /api/v3/qr-payment/transactions/{id}`

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string/integer | Yes | Transaction ID |

#### Example Request

```
GET /api/v3/qr-payment/transactions/1
```

#### Success Response (200 OK)

```json
{
    "status": true,
    "data": {
        "transaction": {
            "id": "uuid-1",
            "transaction_id": "QR-20260112-ABCD1234",
            "amount": 150.00,
            "currency": "USD",
            "status": "APPROVED",
            "transaction_type": "SALE",
            "payment_method": "qr_code",
            "qr_type": "DYNAMIC",
            "qr_code": "QR123456789ABCDEF",
            "customer_name": "John Doe",
            "customer_id": "CUST12345",
            "customer_phone": "+1234567890",
            "customer_email": "john.doe@example.com",
            "wallet_provider": "PayWallet",
            "invoice_no": "QR-INV-20260112-ABCD1234",
            "rrn": "260112543938",
            "auth_code": "123456",
            "trace_no": "543938",
            "batch_no": "001",
            "order_number": "ORD-12345",
            "description": "Payment for order #12345",
            "notes": "Express delivery requested",
            "terminal": {
                "id": 1,
                "name": "Terminal 001"
            },
            "merchant": {
                "id": 1,
                "name": "Merchant Name"
            },
            "created_at": "2026-01-12T14:30:00Z",
            "updated_at": "2026-01-12T14:30:00Z"
        }
    }
}
```

#### Error Response (404 Not Found)

```json
{
    "status": false,
    "message": "QR transaction not found",
    "Error_Code": "QR_TRANSACTION_NOT_FOUND"
}
```

---

## QR Code Types

### STATIC QR Code
- Fixed QR code that can be used multiple times
- Typically displayed at merchant location
- Amount may or may not be predetermined
- Example use cases: Store checkout, bill payments

### DYNAMIC QR Code
- One-time use QR code
- Generated for specific transaction
- Contains transaction-specific data
- Example use cases: Online checkout, invoice payments

---

## Webhook Events

The API triggers the following webhook events:

### Transaction Events
- `transaction.created` - When a QR payment transaction is created
- `transaction.approved` - When a QR payment is approved
- `transaction.declined` - When a QR payment is declined
- `transaction.failed` - When a QR payment fails

### Payment Events
- `payment.succeeded` - When a payment succeeds
- `payment.failed` - When a payment fails

### QR-Specific Events
- `qr_payment.succeeded` - When a QR payment specifically succeeds

---

## Transaction Statuses

| Status | Description |
|--------|-------------|
| `PENDING` | Transaction is being processed |
| `APPROVED` | Transaction approved successfully |
| `COMPLETED` | Transaction completed |
| `DECLINED` | Transaction declined by payment gateway |
| `FAILED` | Transaction failed due to error |

---

## Error Codes

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `TERMINAL_NOT_FOUND` | 404 | Terminal not found or inactive |
| `DUPLICATE_TRANSACTION` | 409 | Duplicate transaction detected |
| `QR_PAYMENT_ERROR` | 500 | General QR payment processing error |
| `QR_TRANSACTION_NOT_FOUND` | 404 | QR transaction not found |
| `QR_TRANSACTION_FETCH_ERROR` | 500 | Error fetching QR transactions |

---

## Testing

### Postman Collection

Import the provided `QR_Payment_API_V3_Postman_Collection.json` file into Postman to test all endpoints.

### Test Data

#### Approved Transaction
Use QR codes starting with "QR" and amounts less than 1000 for simulated approved transactions (95% approval rate).

#### Declined Transaction
The simulation has a 5% decline rate. Declined transactions will have one of these reasons:
- Insufficient balance
- QR code expired
- Invalid QR code
- Wallet temporarily unavailable
- Transaction limit exceeded

---

## Integration Example (JavaScript/Node.js)

```javascript
const axios = require('axios');

async function processQrPayment(qrCode, amount) {
    try {
        const response = await axios.post(
            'https://your-domain.com/api/v3/qr-payment',
            {
                transactionDetails: {
                    amount: amount,
                    currency: 'USD',
                    timestamp: new Date().toISOString(),
                    transactionType: 'SALE'
                },
                qrData: {
                    qrCode: qrCode,
                    qrType: 'DYNAMIC',
                    customerName: 'John Doe',
                    walletProvider: 'PayWallet'
                },
                merchantDetails: {
                    merchantId: '1',
                    terminalId: '1'
                },
                additionalData: {
                    description: 'Product purchase'
                }
            },
            {
                headers: {
                    'X-API-Key': 'your-api-key-here',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }
        );

        if (response.data.status) {
            console.log('Payment successful!');
            console.log('Transaction ID:', response.data.data.transaction.transaction_id);
            console.log('Status:', response.data.data.transaction.status);
            return response.data.data.transaction;
        }
    } catch (error) {
        console.error('Payment failed:', error.response?.data || error.message);
        throw error;
    }
}

// Usage
processQrPayment('QR123456789ABCDEF', 150.00);
```

---

## Best Practices

1. **Always validate QR code data** before sending to the API
2. **Handle errors gracefully** and display appropriate messages to users
3. **Implement retry logic** for network failures (but not for business logic errors)
4. **Store transaction IDs** for reconciliation and support
5. **Use webhooks** for real-time transaction updates instead of polling
6. **Implement duplicate detection** on your side as an additional safety measure
7. **Log all API requests and responses** for debugging and audit trails
8. **Secure your API key** - never expose it in client-side code

---

## Support

For technical support or questions about the QR Payment API:
- Email: support@your-domain.com
- Documentation: https://docs.your-domain.com
- API Status: https://status.your-domain.com

---

## Changelog

### Version 3.0.0 (2026-01-12)
- Initial release of QR Payment API V3
- Support for STATIC and DYNAMIC QR codes
- Full webhook integration
- Transaction history and reporting
- Duplicate transaction detection
- Multi-currency support












