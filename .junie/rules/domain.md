# Domain Modeling

Use rich domain models.

Entities must:

- enforce invariants
- hide internal state
- expose behavior via methods

Avoid:

- public mutable properties
- setter-based models

Prefer:

Value Objects
Aggregates
Domain Events
Repository interfaces

Example:

Bad

$order->status = 'paid';

Good

$order->markAsPaid();
