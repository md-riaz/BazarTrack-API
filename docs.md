# API Documentation

This document describes the available HTTP endpoints for the BazarTrack API. All responses are JSON encoded.

## Authentication

### `POST /api/auth/login`
Authenticate a user.

**Body parameters**
- `email` – user email
- `password` – user password

**Response**
```json
{
  "token": "<string>",
  "user": {
    "id": 1,
    "name": "Example User",
    "email": "user@example.com",
    "role": "owner"
  }
}
```

### `POST /api/auth/logout`
Invalidate the current token.

**Response**
```json
{
  "status": "logged_out"
}
```

### `GET /api/auth/me`
Return basic information about the authenticated user.

**Response**
```json
{
  "id": 1,
  "name": "Example User",
  "email": "user@example.com",
  "role": "owner"
}
```

### `POST /api/auth/refresh`
Issue a new token.

**Response**
```json
{
  "token": "<string>"
}
```

---

## Orders

### `GET /api/orders`
List all orders.

**Response**
```json
[
  {
    "id": 1,
    "created_by": 1,
    "assigned_to": 2,
    "status": "pending",
    "created_at": "2024-01-02 11:00:00",
    "completed_at": null
  }
]
```

### `POST /api/orders`
Create a new order. Only users with the `owner` role may create orders.

**Response**
```json
{
  "id": 5,
  "created_by": 1,
  "assigned_to": null,
  "status": "pending",
  "created_at": "2024-01-03 09:00:00",
  "completed_at": null
}
```

**Body parameters**
- `created_by` – user id of the owner creating the order (required)
- `assigned_to` – user id of the assistant the order is assigned to (optional)
- `status` – order status string (required)
- `created_at` – datetime string (required)
- `completed_at` – datetime string (optional)

### `GET /api/orders/{id}`
Retrieve a single order by id.

**Response**
```json
{
  "id": 1,
  "created_by": 1,
  "assigned_to": 2,
  "status": "pending",
  "created_at": "2024-01-02 11:00:00",
  "completed_at": null
}
```

### `PUT /api/orders/{id}`
Update an existing order.

**Response**
```json
{
  "id": 1,
  "assigned_to": 2,
  "status": "in_progress",
  "completed_at": null
}
```

**Body parameters**
- `assigned_to` – id of the assistant (optional)
- `status` – order status (required)
- `completed_at` – datetime when completed (optional)

### `DELETE /api/orders/{id}`
Remove an order.

**Response**
```json
{
  "deleted": true
}
```

### `POST /api/orders/{id}/assign`
Assign an order to a user.

**Response**
```json
{
  "id": 1,
  "assigned_to": 2,
  "assigned_by": 1
}
```

**Body parameters**
- `user_id` – id of the user the order will be assigned to
- `assigned_by` – id of the user performing the assignment

Owners may assign any order; assistants may assign orders to themselves.

### `POST /api/orders/{id}/complete`
Mark an order as completed.

**Response**
```json
{
  "id": 1,
  "status": "completed",
  "completed_at": "2024-01-05 10:00:00"
}
```

**Body parameters**
- `user_id` – id of the assistant completing the order
- `completed_at` – completion datetime (optional)

Only assistants can mark orders as completed.

---

## Order Items

### `GET /api/order_items`
List all order items.

**Response**
```json
[
  {
    "id": 1,
    "order_id": 1,
    "product_name": "Milk",
    "quantity": 2,
    "unit": "liters",
    "status": "pending"
  }
]
```

### `GET /api/order_items/{order_id}`
List items for a specific order.

**Response**
```json
[
  {
    "id": 1,
    "order_id": 1,
    "product_name": "Milk",
    "quantity": 2,
    "unit": "liters",
    "status": "pending"
  }
]
```

### `GET /api/order_items/{order_id}/{id}`
Retrieve an item by order and item id.

**Response**
```json
{
  "id": 1,
  "order_id": 1,
  "product_name": "Milk",
  "quantity": 2,
  "unit": "liters",
  "status": "pending"
}
```

### `POST /api/order_items`
Create a new order item. Only owners can add items.

**Response**
```json
{
  "id": 3,
  "order_id": 2,
  "product_name": "Bread",
  "quantity": 1,
  "unit": "pc",
  "estimated_cost": 2.5,
  "actual_cost": null,
  "status": "pending"
}
```

**Body parameters**
- `order_id` – associated order id (required)
- `product_name` – item name (required)
- `quantity` – quantity value (required)
- `unit` – measurement unit (optional)
- `estimated_cost` – estimated price (optional)
- `actual_cost` – final price (optional)
- `status` – item status (required)
- `user_id` – id of the owner creating the item (required)

### `PUT /api/order_items/{order_id}/{id}`
Update an order item. Assistants and owners may update items.

**Response**
```json
{
  "id": 1,
  "order_id": 1,
  "product_name": "Milk",
  "quantity": 3,
  "unit": "liters",
  "estimated_cost": 2.5,
  "actual_cost": 2.4,
  "status": "purchased"
}
```

**Body parameters**
- `product_name` – item name (required)
- `quantity` – quantity value (required)
- `unit` – measurement unit (optional)
- `estimated_cost` – estimated price (optional)
- `actual_cost` – final price (optional)
- `status` – item status (required)
- `user_id` – id of the user making the change (required)

If `actual_cost` is supplied, the amount is debited from the user's wallet.

### `DELETE /api/order_items/{order_id}/{id}`
Delete an order item. Only owners can delete items.

**Response**
```json
{
  "deleted": true
}
```

**Body parameters**
- `user_id` – id of the owner deleting the item

---

## Payments

### `GET /api/payments`
List recorded payments.

**Response**
```json
[
  {
    "id": 1,
    "user_id": 2,
    "amount": 100.0,
    "type": "credit",
    "created_at": "2024-01-03 08:00:00"
  }
]
```

### `POST /api/payments`
Record a payment or expense.

**Response**
```json
{
  "id": 2,
  "user_id": 2,
  "amount": 50.0,
  "type": "debit",
  "created_at": "2024-01-03 10:00:00"
}
```

**Body parameters**
- `user_id` – wallet owner id (required)
- `amount` – monetary amount (required)
- `type` – `credit` or `debit` (required)
- `created_at` – datetime string (required)
- `created_by` – id of the user creating the payment (required)

Credits may only be created by owners, while debits are restricted to assistants. A new payment updates the user's wallet balance and transactions.

---

## Wallet

### `GET /api/wallet/{user_id}`
Return the wallet balance for a user.

**Response**
```json
{
  "user_id": 2,
  "balance": 150.0
}
```

### `GET /api/wallet/{user_id}/transactions`
List wallet transactions for a user in reverse chronological order.

**Response**
```json
[
  {
    "id": 1,
    "user_id": 2,
    "amount": 100.0,
    "type": "credit",
    "created_at": "2024-01-03 08:00:00"
  },
  {
    "id": 2,
    "user_id": 2,
    "amount": 50.0,
    "type": "debit",
    "created_at": "2024-01-03 10:00:00"
  }
]
```

---

## History Logs

### `GET /api/history`
List all history log entries.

**Response**
```json
[
  {
    "id": 1,
    "entity_type": "order",
    "entity_id": 1,
    "action": "created",
    "changed_by_user_id": 1,
    "timestamp": "2024-01-03 09:00:00"
  }
]
```

### `GET /api/history/{entity}/{id}`
List log entries related to a particular entity (e.g. `order`).

**Response**
```json
[
  {
    "id": 1,
    "entity_type": "order",
    "entity_id": 1,
    "action": "created",
    "changed_by_user_id": 1,
    "timestamp": "2024-01-03 09:00:00"
  }
]
```

### `POST /api/history`
Create a history log entry.

**Response**
```json
{
  "id": 5,
  "entity_type": "order",
  "entity_id": 1,
  "action": "status_updated",
  "changed_by_user_id": 2,
  "timestamp": "2024-01-05 10:00:00"
}
```

**Body parameters**
- `entity_type` – type name (required)
- `entity_id` – entity id (required)
- `action` – short action description (required)
- `changed_by_user_id` – id of the user who made the change (required)
- `timestamp` – datetime string (required)
- `data_snapshot` – JSON data representing the entity state (required)

### `DELETE /api/history/{id}`
Delete a history log entry.

**Response**
```json
{
  "deleted": true
}
```

---

## Analytics

### `GET /api/analytics/dashboard`
Return overall counts and total revenue.

**Response**
```json
{
  "orders": 10,
  "order_items": 25,
  "revenue": 1500.0
}
```

### `GET /api/analytics/reports`
Return monthly order counts and revenue totals.

**Response**
```json
[
  {
    "month": "2024-01",
    "order_count": 5,
    "revenue": 500.0
  },
  {
    "month": "2024-02",
    "order_count": 5,
    "revenue": 1000.0
  }
]
```

---

All timestamps follow the `YYYY-MM-DD HH:MM:SS` format. Endpoints may return standard HTTP error codes for validation or authorization failures.
