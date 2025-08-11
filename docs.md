# API Documentation

This document describes the available HTTP endpoints for the BazarTrack API. All responses are JSON encoded.

## Workflow guide

The API supports an owner–assistant purchasing workflow. The list below shows how common UI elements map to HTTP endpoints.

### Owner dashboard
- **Stats (total, assigned, in progress, completed)** – `GET /api/analytics/dashboard` returns aggregated counts, and `GET /api/orders` lists all orders for client-side filtering.
- **Assistant wallet summary & advance history** – `GET /api/wallet/{assistant_id}` for current balance and `GET /api/wallet/{assistant_id}/transactions` for advances and expenses.
- **Order activity timeline** – `GET /api/history/order/{order_id}` shows a chronological log for a specific order.

### Assistant dashboard
- **Assigned orders** – `GET /api/orders` with client-side filtering by `assigned_to` or `status`.
- **Wallet balance** – `GET /api/wallet/{assistant_id}`.

### Purchase entry / order detail
- **Update item status and costs** – `PUT /api/order_items/{order_id}/{item_id}`.
- **Record expenses or advances** – `POST /api/payments` with `type` set to `debit` or `credit`.
- **Timeline and action log** – actions are stored via `POST /api/history` and retrievable with `GET /api/history/order/{order_id}`.

### Order Management
- **Create order** – Owner makes a new order and can submit initial line items in the same request using POST /api/orders
- **Add or edit items later** – Additional line items can be added with POST /api/order_items, and both owners and assistants may update an item (including costs and status) via PUT /api/order_items/{order_id}/{id}
- **Assign orders** – Owners may assign orders to an assistant, while assistants can self‑assign using POST /api/orders/{id}/assign

### Wallet & Payments
- **Advance/expense entries** – A payment entry (credit or debit) is recorded through POST /api/payments; credits (advances) are created by owners, while debits are typically recorded when assistants update an item with an actual cost
- **Wallet state** – Retrieve a user’s wallet balance and transaction history with GET /api/wallet/{user_id} and GET /api/wallet/{user_id}/transactions
- **Automatic deductions** – When an assistant supplies actual_cost for an item via PUT /api/order_items/{order_id}/{id}, that amount is automatically debited from the assistant’s wallet

### Activity Timeline & Logging
- **History logging** – Any action (order creation, item updates, assignments, etc.) can be recorded via POST /api/history, and individual entity timelines are retrieved with GET /api/history/{entity}/{id}
- These endpoints allow building the “Order Activity Timeline” and “Action Log” views you described.

### Dashboards & Analytics
- **Owner Dashboard stats** – Overall counts and revenue figures come from GET /api/analytics/dashboard; monthly breakdowns are available via GET /api/analytics/reports

### Example flow
1. Owner creates an order – `POST /api/orders`.
2. Owner optionally gives an advance – `POST /api/payments` (`credit`).
3. Assistant self-assigns or is assigned – `POST /api/orders/{id}/assign`.
4. Assistant updates item costs – `PUT /api/order_items/{order_id}/{item_id}` and logs expenses with `POST /api/payments` (`debit`).
5. System logs each action – `POST /api/history`.
6. Owner reviews the timeline – `GET /api/history/order/{id}`.

Error responses share a common structure:
```json
{ "error": "<message>" }
```

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
Requires an `Authorization: Bearer <token>` header.

**Response**
```json
{
  "status": "logged_out"
}
```

### `GET /api/auth/me`
Return basic information about the authenticated user.
Requires an `Authorization: Bearer <token>` header.

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
Requires an `Authorization: Bearer <token>` header.

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
Create a new order. Only users with the `owner` role may create orders. Multiple
items can be submitted together with the order by providing an `items` array.

**Body parameters**
- `assigned_to` – user id of the assistant the order is assigned to (optional).
  If omitted, the order is left unassigned and an assistant may claim it later.
- `status` – order status string (required)
- `items` – array of order item objects (optional). Each item accepts the same
  fields as `POST /api/order_items` except for `order_id`.

**Request example**
```json
{
  "status": "pending",
  "items": [
    { "product_name": "Milk", "quantity": 2, "unit": "liters", "status": "pending" },
    { "product_name": "Bread", "quantity": 1, "unit": "pc", "status": "pending" }
  ]
}
```

**Response**
```json
{
  "id": 5,
  "created_by": 1,
  "assigned_to": null,
  "status": "pending",
  "created_at": "2024-01-03 09:00:00",
  "completed_at": null,
  "items": [
    {
      "id": 1,
      "order_id": 5,
      "product_name": "Milk",
      "quantity": 2,
      "unit": "liters",
      "estimated_cost": null,
      "actual_cost": null,
      "status": "pending"
    },
    {
      "id": 2,
      "order_id": 5,
      "product_name": "Bread",
      "quantity": 1,
      "unit": "pc",
      "estimated_cost": null,
      "actual_cost": null,
      "status": "pending"
    }
  ]
}
```

Timestamps are generated by the server. The `created_by` value is taken from the
authenticated token.

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
Completion timestamps are generated by the server when the status becomes `completed`.

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
The assigning user is taken from the authenticated token.

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
The assistant completing the order is inferred from the authenticated token.

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
The creating user is taken from the authenticated token.

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
The user performing the update is inferred from the authenticated token.

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
The deleting user is taken from the authenticated token.

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
Timestamps are generated by the server.
The `created_by` value comes from the authenticated token.

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
- `data_snapshot` – JSON data representing the entity state (required)
Timestamps are generated by the server.
The `changed_by_user_id` value is taken from the authenticated token.

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

## Assistants

### `GET /api/assistants`
List assistant users.

**Query parameters**
- `with_balance` – if `true`, include current wallet balances.

**Response**
```json
[
  {
    "id": 2,
    "name": "Assistant Jane"
  }
]
```

**Response with balance**
```json
[
  {
    "id": 2,
    "name": "Assistant Jane",
    "balance": 150.0
  }
]
```

---

All timestamps follow the `YYYY-MM-DD HH:MM:SS` format and are generated using the `Asia/Dhaka` timezone. Endpoints may return standard HTTP error codes for validation or authorization failures. Error bodies follow the structure shown above.
