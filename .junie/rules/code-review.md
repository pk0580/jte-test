# Code Review Guidelines

Before returning generated code, perform an internal code review.

The code must be evaluated as if it were reviewed by a senior engineer.

---

# Architecture Review

Verify that the code respects the architecture.

Check:

- Domain layer has no framework dependencies
- Application layer orchestrates domain logic
- Infrastructure implements interfaces
- Controllers remain thin

Dependency direction must be:

UI -> Application -> Domain

---

# DDD Review

Check domain model quality.

Ensure:

- entities enforce invariants
- domain logic is inside entities or domain services
- value objects are used when appropriate

Avoid:

- anemic domain models
- public mutable properties

---

# Repository Review

Check repository design.

Ensure:

- repositories do not contain business logic
- repositories encapsulate queries
- repositories do not grow too large

Avoid fat repositories.

Prefer read repositories for complex queries.

---

# Doctrine Review

Check ORM usage.

Verify:

- no N+1 queries
- no lazy loading inside loops
- explicit joins where needed
- pagination for large datasets

Avoid:

findAll() on large tables.

---

# Performance Review

Assume high traffic and large datasets.

Check:

- pagination for large collections
- batch processing for large loops
- streaming when exporting data

Avoid:

loading thousands of entities into memory.

---

# API Review

Verify API consistency.

Ensure:

- versioned endpoints
- consistent response format
- proper HTTP status codes
- request validation

Avoid exposing entities directly.

Use DTO for responses.

---

# Concurrency Review

Check concurrent safety.

Ensure operations are safe under concurrent requests.

Use:

- idempotent operations
- database constraints
- optimistic or pessimistic locking

---

# Async Processing Review

Heavy operations must run asynchronously.

Examples:

email sending
external integrations
large reports

Use Symfony Messenger.

---

# Security Review

Ensure secure coding.

Check:

- input validation
- parameter binding for SQL
- no sensitive data in logs

Avoid trusting user input.

---

# Final Rule

If a better architectural approach exists, prefer it over simpler but less scalable solutions.

Always prioritize:

maintainability  
scalability  
testability
