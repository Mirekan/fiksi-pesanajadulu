# Restaurant Ordering API Documentation

## Base URL
```
{your-domain}/api/
```

## Authentication
All endpoints except login, register, and webhook require authentication using Laravel Sanctum.
Include the bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Endpoints

### Authentication

#### POST /login
Login user and get authentication token.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

**Response:**
```json
{
    "token": "your-bearer-token",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com"
    }
}
```

#### POST /register
Register new user.

**Request:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

#### POST /logout
Logout user (requires auth).

### Orders

#### POST /order/store
Create a new order with 50% advance payment.

**Request:**
```json
{
    "user_id": 1,
    "table_id": 2,
    "amount": 25000,
    "reservation_time": "2024-01-02T18:00:00Z",
    "order_items": [
        {
            "menu_id": 1,
            "quantity": 2,
            "price": 25000
        },
        {
            "menu_id": 3,
            "quantity": 1,
            "price": 25000
        }
    ]
}
```

**Note:** The `amount` field should be 50% of the total order value (calculated by client). The server will calculate the full order amount from order_items and store it, but use the provided amount for Midtrans payment.

**Response:**
```json
{
    "snap_url": "https://app.sandbox.midtrans.com/snap/v2/vtweb/abc123"
}
```

#### GET /order
Get all orders for authenticated user.

**Response:**
```json
{
    "orders": [
        {
            "id": "9d1e3a2b-4c5d-6e7f-8a9b-0c1d2e3f4a5b",
            "user_id": 1,
            "table_id": 2,
            "amount": 50000,
            "status": "pending",
            "created_at": "2024-01-01T10:00:00Z",
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "user@example.com"
            },
            "table": {
                "id": 2,
                "number": "T02",
                "capacity": 4
            },
            "orderItems": [
                {
                    "id": 1,
                    "menu_id": 1,
                    "quantity": 2,
                    "price": 25000,
                    "menu": {
                        "id": 1,
                        "name": "Nasi Goreng",
                        "price": 25000,
                        "description": "Indonesian fried rice"
                    }
                }
            ],
            "payment": {
                "id": 1,
                "amount": 25000,
                "remaining_amount": 25000,
                "status": "pending",
                "type": "advance",
                "midtrans_order_id": "ORDER-123"
            }
        }
    ]
}
```

#### GET /order/{id}
Get specific order details for authenticated user.

**Response:**
```json
{
    "order": {
        "id": "9d1e3a2b-4c5d-6e7f-8a9b-0c1d2e3f4a5b",
        "user_id": 1,
        "table_id": 2,
        "amount": 50000,
        "status": "pending",
        "created_at": "2024-01-01T10:00:00Z",
        "user": { /* user object */ },
        "table": { /* table object */ },
        "orderItems": [ /* array of order items with menu details */ ],
        "payment": { /* payment object */ }
    }
}
```

### Payments

#### POST /payment/complete
Handle remaining payment (50%) at restaurant.

**Request:**
```json
{
    "order_id": "9d1e3a2b-4c5d-6e7f-8a9b-0c1d2e3f4a5b",
    "payment_method": "cash",
    "staff_id": 1
}
```

**Response:**
```json
{
    "message": "Remaining payment completed successfully",
    "order": {
        "id": "9d1e3a2b-4c5d-6e7f-8a9b-0c1d2e3f4a5b",
        "status": "completed"
    },
    "total_paid": 50000,
    "advance_paid": 25000,
    "remaining_paid": 25000
}
```

#### GET /payment/status
Get payment status for an order.

**Request Parameters:**
- `order_id`: Order ID

**Response:**
```json
{
    "advance_payment": {
        "status": "completed",
        "amount": 25000
    },
    "remaining_payment": {
        "status": "pending",
        "amount": 25000
    },
    "total_paid": 25000,
    "total_amount": 50000
}
```

#### POST /order/cancel
Cancel an order (only if advance payment is pending).

**Request:**
```json
{
    "order_id": "9d1e3a2b-4c5d-6e7f-8a9b-0c1d2e3f4a5b"
}
```

**Response:**
```json
{
    "message": "Order cancelled successfully"
}
```

### Resources (Read-only for mobile app)

#### GET /menu
Get all available menu items.

#### GET /table
Get all tables.

#### GET /restaurant
Get restaurant information.

## Payment Workflow

1. **Order Creation**: POST to `/order/store` with items
2. **Advance Payment**: Use returned `snap_url` to complete 50% payment via Midtrans
3. **Payment Verification**: Check status with `/payment/status`
4. **Remaining Payment**: Complete at restaurant using `/payment/handle`

## Order Status Flow
- `pending` → Order created, advance payment pending
- `confirmed` → Advance payment completed, being prepared
- `ready` → Order ready for pickup/serving
- `completed` → Both payments completed, order fulfilled
- `cancelled` → Order cancelled

## Payment Status
- `pending` → Payment not yet completed
- `completed` → Payment successful
- `failed` → Payment failed
- `cancelled` → Payment cancelled

## Stock Management
- Stock is decremented when order is created
- Stock is restored if advance payment fails or order is cancelled
- Check menu availability before allowing orders

## Error Responses
All errors return appropriate HTTP status codes with JSON response:
```json
{
    "message": "Error description",
    "errors": {
        "field": ["validation error message"]
    }
}
```

## Important Notes

1. **50% Advance Payment**: Only 50% of total order amount is paid online
2. **Remaining Payment**: Must be completed at restaurant before order fulfillment
3. **Stock Management**: Items are reserved when order is created
4. **Authentication**: All order and payment endpoints require authentication
5. **Webhook**: Midtrans webhook URL is `/payment/notification` (no auth required)
