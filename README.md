# BazarTrack-API

BazarTrack-API provides a JSON REST service for a smart purchase and money management system. It is built with plain PHP and PDO and does not rely on a framework.

## Requirements

- PHP 8.0+
- MySQL

Copy `.env.example` to `.env` and update the values before running the API.
It defines variables like `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` and `ALLOWED_ORIGIN`.

The `config.php` file automatically loads variables from this file at runtime.
The following variables are required:

- `DB_HOST` – database host name
- `DB_NAME` – database name
- `DB_USER` – database username
- `DB_PASSWORD` – database password 

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

### CORS configuration

Set an environment variable called `ALLOWED_ORIGIN` with the URL allowed to access
the API. When defined, `index.php` will include an `Access-Control-Allow-Origin`
header for that origin.

## Example data

The repository provides `seed.sql` with the full schema and seed rows. It
creates sample users (including **owner** and **assistant** roles stored in a
`role` column), orders, wallets and related records. Import it into an empty
MySQL database to start quickly:

```bash
mysql < seed.sql
```

## Endpoints

After logging in, include the returned token in requests to secured endpoints using the header:

```text
Authorization: Bearer <token>
```

Endpoints annotated with **(🔒 requires token)** need this header.

### Authentication
- `POST /api/auth/login` – log in with email and password.
- `POST /api/auth/logout` – invalidate the current token. **(🔒 requires token)**
- `GET /api/auth/me` – return information about the current user. **(🔒 requires token)**
- `POST /api/auth/refresh` – issue a new token. **(🔒 requires token)**
- *No registration endpoint is provided.*

### Orders
- `GET /api/orders` – list orders. **(🔒 requires token)**
- `POST /api/orders` – create an order. **(🔒 requires token)**
- `GET /api/orders/{id}` – get a specific order. **(🔒 requires token)**
- `PUT /api/orders/{id}` – update an order. **(🔒 requires token)**
- `DELETE /api/orders/{id}` – delete an order. **(🔒 requires token)**
- `POST /api/orders/{id}/assign` – assign an order to a user. **(🔒 requires token)**
- `POST /api/orders/{id}/complete` – mark an order as completed. **(🔒 requires token)**

### Order items
- `GET /api/order_items` – list all items. **(🔒 requires token)**
- `GET /api/order_items/{order_id}` – list items for an order. **(🔒 requires token)**
- `GET /api/order_items/{order_id}/{id}` – get a single item. **(🔒 requires token)**
- `POST /api/order_items` – create an item. **(🔒 requires token)**
- `PUT /api/order_items/{order_id}/{id}` – update an item. **(🔒 requires token)**
- `DELETE /api/order_items/{order_id}/{id}` – delete an item. **(🔒 requires token)**

### Payments
- `GET /api/payments` – list payments. **(🔒 requires token)**
- `POST /api/payments` – create a payment. **(🔒 requires token)**

### Wallet
- `GET /api/wallet/{user_id}` – retrieve the balance for a user. **(🔒 requires token)**
- `GET /api/wallet/{user_id}/transactions` – list wallet transactions. **(🔒 requires token)**

### History
- `GET /api/history` – list history logs. **(🔒 requires token)**
- `GET /api/history/{entity}/{id}` – logs for a specific entity instance. **(🔒 requires token)**
- `POST /api/history` – create a log entry. **(🔒 requires token)**
- `DELETE /api/history/{id}` – delete a log entry. **(🔒 requires token)**

### Analytics
- `GET /api/analytics/dashboard` – basic dashboard statistics. **(🔒 requires token)**
- `GET /api/analytics/reports` – monthly reports. **(🔒 requires token)**

## Example workflow

The sequence below shows how the API endpoints support a typical owner/assistant
scenario:

1. **Owner creates an order**
   - `POST /api/orders` with an `items` array to list products.
   - Optionally assign immediately using `POST /api/orders/{id}/assign`.

2. **Owner provides advance to the assistant**
   - `POST /api/payments` with `type` set to `credit` to add funds to the assistant’s wallet.
   - Wallet balance and past advances are available via `GET /api/wallet/{user_id}` and
     `GET /api/wallet/{user_id}/transactions`.

3. **Assistant claims or receives the order**
   - Assistants may self-assign using `POST /api/orders/{id}/assign` or be assigned by an owner.
   - Assigned orders are retrieved with `GET /api/orders`.

4. **Assistant records purchases**
   - Update each item with actual costs using `PUT /api/order_items/{order_id}/{id}`.
   - Each update debits the assistant wallet automatically.

5. **Completion and auditing**
   - When work is done, call `POST /api/orders/{id}/complete`.
   - Activity timelines are available from `GET /api/history/order/{id}`.
   - Owners can view overall statistics using `GET /api/analytics/dashboard`.

Responses are returned in JSON format. Endpoints marked with **(🔒 requires token)** must include the `Authorization` header shown above.

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on contributing to this project.
