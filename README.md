# BazarTrack-API

BazarTrack-API provides a JSON REST service for a smart purchase and money management system. It is built with plain PHP and PDO and does not rely on a framework.

## Requirements

- PHP 8.0+
- MySQL

Copy `.env.example` to `.env` and update the values before running the API.
It defines variables like `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` and `ALLOWED_ORIGIN`.

## User setup

An initial owner account must exist in the `users` table before using the API.
You can create it manually or import the provided `seed.sql` script which
contains the full schema and a few sample records:

```sql
INSERT INTO users (name, email, password, role)
VALUES (
    'Grace Morrison',
    'owner@example.com',
    '$2y$10$bJCOemcxy.RQWdS9evmCeeH9yryaa4sraTvcWoHPjj0xxKSi7htW6',
    'owner'
);
```

The password values in `seed.sql` are produced using `password_hash()`.
Sample credentials you can use for testing:
- `owner@example.com` / `password`
- `grace.morrison@example.com` / `password1`
- `leo.martinez@example.com` / `password2`

Use these when calling `POST /api/auth/login`.

Additional owners or assistants should be created directly in the database.
The API exposes no endpoints to register or modify users.

## Running

Use PHP's built-in server for quick testing:

```bash
php -S localhost:8000 index.php
```

All requests are routed through `index.php`.

## Example data

The repository provides `seed.sql` with the full schema and seed rows. It
creates sample users (including **owner** and **assistant** roles stored in a
`role` column), orders, wallets and related records. Import it into an empty
MySQL database to start quickly:

```bash
mysql < seed.sql
```

## Endpoints

### Authentication
- `POST /api/auth/login` – log in with email and password.
- `POST /api/auth/logout` – invalidate the current token.
- `GET /api/auth/me` – return information about the current user.
- `POST /api/auth/refresh` – issue a new token.
- *No registration endpoint is provided.*

### Orders
- `GET /api/orders` – list orders.
- `POST /api/orders` – create an order.
- `GET /api/orders/{id}` – get a specific order.
- `PUT /api/orders/{id}` – update an order.
- `DELETE /api/orders/{id}` – delete an order.
- `POST /api/orders/{id}/assign` – assign an order to a user.
- `POST /api/orders/{id}/complete` – mark an order as completed.

### Order items
- `GET /api/order_items` – list all items.
- `GET /api/order_items/{order_id}` – list items for an order.
- `GET /api/order_items/{order_id}/{id}` – get a single item.
- `POST /api/order_items` – create an item.
- `PUT /api/order_items/{order_id}/{id}` – update an item.
- `DELETE /api/order_items/{order_id}/{id}` – delete an item.

### Payments
- `GET /api/payments` – list payments.
- `POST /api/payments` – create a payment.

### Wallet
- `GET /api/wallet/{user_id}` – retrieve the balance for a user.
- `GET /api/wallet/{user_id}/transactions` – list wallet transactions.

### History
- `GET /api/history` – list history logs.
- `GET /api/history/{entity}/{id}` – logs for a specific entity instance.
- `POST /api/history` – create a log entry.
- `DELETE /api/history/{id}` – delete a log entry.

### Analytics
- `GET /api/analytics/dashboard` – basic dashboard statistics.
- `GET /api/analytics/reports` – monthly reports.

Responses are returned in JSON format. Some endpoints expect an authentication token (token handling is simplified in this example).

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on contributing to this project.
