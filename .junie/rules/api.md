# API Design Guidelines

These rules apply when designing HTTP APIs.

Goals:

- consistent API design
- scalable endpoints
- predictable responses
- easy client integration

---

# API Versioning

All APIs must be versioned.

Example:

/api/v1/orders
/api/v1/customers

Never expose unversioned APIs.

---

# Controller Responsibilities

Controllers must remain thin.

Controllers should:

- validate input
- create command or query
- call handler
- return response

Controllers must not contain business logic.

---

# Request DTO

Requests must be mapped to DTO objects.

Example:

CreateOrderRequest

DTO must contain validation rules.

---

# Response Format

All API responses must follow a consistent format.

Example:

{
"data": {},
"meta": {},
"errors": []
}

---

# Error Handling

Errors must use consistent structure.

Example:

{
"error": {
"code": "order_not_found",
"message": "Order not found"
}
}

Avoid returning raw exceptions.

---

# Pagination

Endpoints returning collections must support pagination.

Example:

GET /api/v1/orders?page=1&limit=20

Response must include metadata.

Example:

{
"data": [],
"meta": {
"page": 1,
"limit": 20,
"total": 120
}
}

---

# Filtering

Filtering must be explicit.

Example:

GET /api/v1/orders?status=paid

---

# Sorting

Sorting must be supported when relevant.

Example:

GET /api/v1/orders?sort=createdAt

---

# Idempotent Operations

Certain endpoints must be idempotent.

Examples:

payment operations
order creation with idempotency key

Support header:

Idempotency-Key

---

# HTTP Status Codes

Use proper HTTP codes.

200 OK
201 Created
204 No Content
400 Bad Request
404 Not Found
409 Conflict
500 Internal Server Error

---

# Rate Limit Friendly APIs

APIs must be designed to support rate limiting.

Avoid endpoints returning extremely large payloads.

Always support pagination.

---

# Serialization

Use explicit DTO for API responses.

Do not expose Doctrine entities directly.
