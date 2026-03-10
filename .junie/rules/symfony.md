# Symfony Guidelines

Framework: Symfony.

Controllers must remain thin.

Responsibilities:

- validate request
- call application use case
- return response

Never put business logic in controllers.

Use dependency injection.
