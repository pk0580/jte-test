# Service Layer Guidelines

These rules apply when generating application services.

Goal:

- avoid God services
- enforce use case oriented architecture
- maintain clear responsibilities

---

# Avoid Generic Services

Do NOT create generic services such as:

UserService
OrderService
PaymentService
Helper
Manager

These classes usually become large and contain unrelated logic.

---

# Use UseCase Classes

Application logic must be organized into explicit use cases.

Examples:

CreateOrder
CancelOrder
PayOrder
SendInvoice

Each use case represents one business operation.

---

# Use Command / Handler Pattern

Write operations must follow Command / Handler pattern.

Example:

CreateOrderCommand
CreateOrderHandler

Command contains input data.

Handler orchestrates domain logic.

---

# Example Structure

Application/

Command/
CreateOrderCommand

Handler/
CreateOrderHandler

---

# Command

Commands must be simple DTO objects.

Example

readonly class CreateOrderCommand
{
public function __construct(
public int $customerId,
public array $items
) {}
}
