# Engineering Guidelines

All generated code must follow these principles.

Architecture:

- Domain Driven Design
- Clean Architecture
- SOLID

Layer structure:

UI -> Application -> Domain

Infrastructure implements interfaces.

Domain must not depend on frameworks.

---

Code Quality

Prefer:

- immutable objects
- constructor injection
- explicit dependencies

Avoid:

- God services
- static state
- service locators

---

Performance

Assume high traffic and large datasets.

Avoid:

- N+1 queries
- loading large collections

Prefer:

- pagination
- batching
- streaming
- async processing

---

AI behavior

Prefer maintainable architecture over shortcuts.
