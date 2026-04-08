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

- Prefer maintainable architecture over shortcuts.
- Use `.junie/index.md` to find rules and context files for application work.
- Always follow the rules specified in the files listed in `.junie/index.md`.

---

# Execution Guidelines (STRICT)

All commands (tests, composer, bin/console) must be executed inside the `jte-test-php-1` container.

Example:

`docker exec jte-test-php-1 bin/phpunit`
`docker exec jte-test-php-1 composer install`
`docker exec jte-test-php-1 bin/console`

Общайся на русском языке
