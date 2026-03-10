# Domain Layer Rules

These rules apply to files inside src/Domain.

Domain must remain pure.

Do not use:

Symfony
Doctrine
HTTP
serialization

Allowed concepts:

Entities
Value Objects
Domain Services
Domain Events
Repository interfaces

Entities must enforce invariants.

Avoid public mutable properties.

Prefer immutable value objects.
