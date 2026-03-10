# Doctrine ORM Guidelines

These rules apply when working with Doctrine ORM.

Goals:

- avoid N+1 queries
- optimize database access
- prevent memory issues
- design scalable repositories

---

# Entity Design

Entities represent domain objects.

Rules:

- entities must not expose mutable public state
- avoid setters when possible
- protect invariants with domain methods

Bad

$order->status = 'paid';

Good

$order->markAsPaid();

---

# Lazy Loading

Lazy loading must not be used inside loops.

Bad example

foreach ($orders as $order) {
$order->getItems()->count();
}

This causes N+1 queries.

---

# Fetch Strategies

Prefer explicit joins.

Example

SELECT o, i
FROM Order o
LEFT JOIN o.items i

Always think about query cost.

---

# Repository Responsibilities

Repositories must:

- encapsulate queries
- return domain objects or DTO

Avoid writing queries inside controllers.

---

# Large Queries

For large datasets:

Never load thousands of entities.

Prefer:

- pagination
- streaming
- partial selects

Example

SELECT new App\Dto\OrderSummaryDto(
o.id,
o.total,
o.createdAt
)
FROM Order o
