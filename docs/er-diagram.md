# Database ER diagram (Mermaid)

Paste into GitHub, Notion, or [Mermaid Live Editor](https://mermaid.live/).

```mermaid
erDiagram
    users ||--o{ orders : "user_id"
    users ||--o{ carts : "user_id"
    users ||--o{ otp_verifications : "user_id"
    currencies ||--o{ products : "currency_id"
    currencies ||--o{ orders : "currency_id"
    currencies ||--o{ invoices : "currency_id"
    currencies ||--o{ receipts : "currency_id"
    orders ||--o{ order_items : "order_id"
    products ||--o{ order_items : "product_id"
    orders ||--|| invoices : "order_id"
    invoices ||--|| receipts : "invoice_id"
    carts ||--o{ cart_items : "cart_id"
    products ||--o{ cart_items : "product_id"

    users {
        int id PK
        string name
        string email
        string password
        string role
        timestamp email_verified_at
        timestamp deleted_at
    }

    currencies {
        int id PK
        string code
        string name
        int minor_unit
        int is_default
    }

    products {
        int id PK
        string name
        decimal price
        int currency_id FK
        string stripe_price_id
        timestamp deleted_at
    }

    orders {
        int id PK
        int user_id FK
        decimal total_amount
        int currency_id FK
        string status
        string stripe_session_id
        string stripe_payment_intent
        timestamp deleted_at
    }

    order_items {
        int id PK
        int order_id FK
        int product_id FK
        int quantity
        decimal unit_price
    }

    invoices {
        int id PK
        int order_id FK
        int currency_id FK
        string invoice_number
        decimal total_amount
        string status
        date due_date
    }

    receipts {
        int id PK
        int invoice_id FK
        int currency_id FK
        string receipt_number
        string stripe_payment_intent
        decimal amount_paid
        timestamp paid_at
    }

    carts {
        int id PK
        int user_id FK
        string status
        decimal total_amount
        timestamp expires_at
    }

    cart_items {
        int id PK
        int cart_id FK
        int product_id FK
        int quantity
        decimal price
    }

    otp_verifications {
        int id PK
        int user_id FK
        string email
        string intent
        string otp_hash
        timestamp otp_expires_at
        timestamp verified_at
    }

    password_resets {
        int id PK
        string email
        string intent
        string otp_hash
        timestamp otp_expires_at
        string reset_token_hash
    }

    stripe_logs {
        int id PK
        string event_id
        string event_type
        string payload_json
        int processed
        timestamp created_at
    }
```

- `password_resets` has no foreign keys (keyed by email).
- `stripe_logs` has no FKs; `event_id` is **UNIQUE** when set (Stripe `evt_…`) for webhook idempotency; `processed` is `0` until handling finishes. `payload` is JSON in MySQL (`payload_json` here is a label for Mermaid).
