# Repository Design Guidelines

These rules apply when generating repositories.

Goals:

- maintain clean architecture
- avoid fat repositories
- optimize database queries
- support high load systems

---

# Repository Responsibilities

Repositories encapsulate database access.

Repositories must:

- hide ORM details
- provide clear query methods
- return domain objects or DTOs

Repositories must NOT contain business logic.

Business rules belong to Domain or Application layers.

---

# Repository Types

Prefer separating repositories by responsibility.

Write repositories

Responsible for:

- saving aggregates
- retrieving aggregates for modification

Example

OrderRepository

Methods:

save(Order $order)
getById(OrderId $id)

---

Read repositories

Responsible for:

- optimized queries
- projections
- pagination

Example

OrderReadRepository

Methods:

findOrdersForDashboard()
findRecentOrders()

---

# Avoid Fat Repositories

Bad example

OrderRepository

findOrdersByStatus
findOrdersByCustomer
findOrdersByDate
findOrdersByStatusAndCustomer
findOrdersByEverything

This leads to massive repositories.

Prefer specialized read repositories.

---

# DTO Projections

For read-heavy queries prefer DTO projections.

Example

SELECT new App\Dto\OrderListItem(
o.id,
o.total,
o.createdAt
)
FROM Order o
