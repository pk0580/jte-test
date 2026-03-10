# Performance Critical Guidelines

These rules apply to code that may run in high traffic environments.

Assume the system may serve millions of users and large datasets.

---

# Database Queries

Always assume database tables may contain millions of rows.

Avoid:

- SELECT *
- unbounded queries
- loading entire tables

Prefer:

- pagination
- explicit field selection
- indexed filters

Example:

SELECT id, status, created_at
FROM orders
WHERE status = :status
LIMIT 50

---

# Pagination

Endpoints returning collections must support pagination.

Example:

GET /api/v1/orders?page=1&limit=20

Never return unbounded collections.

---

# Memory Usage

Avoid loading large collections into memory.

Bad:

$orders = $orderRepository->findAll();

Good:

use pagination
use iterators
use streaming

---

# Batch Processing

Large datasets must be processed in batches.

Example:

foreach ($orders as $i => $order) {

process($order);

if ($i % 100 === 0) {
$entityManager->flush();
$entityManager->clear();
}

}

---

# Streaming

For exports or large reads use streaming responses.

Avoid building huge arrays before sending responses.

---

# Async Processing

Heavy operations must run asynchronously.

Examples:

- sending emails
- report generation
- integrations
- large imports

Use Symfony Messenger.

---

# External APIs

External calls must support:

timeouts
retries
circuit breakers

Never block request processing for long operations.

---

# Backpressure

The system must avoid overload.

Strategies:

- queues
- rate limiting
- worker scaling

---

# Observability

Performance critical code must support:

logging
metrics
tracing

Important operations must log:

request id
trace id
execution time
