# Project Structure Guidelines

These rules define the structure of the Symfony project.

The project must follow Clean Architecture and Domain Driven Design.

---

# Root Structure

Source code must be organized into the following layers:

src/

Domain/
Application/
Infrastructure/
UI/

Each layer has specific responsibilities.

---

# Domain Layer

The Domain layer contains pure business logic.

Domain must not depend on any framework.

Allowed contents:

Entities
Value Objects
Domain Services
Domain Events
Repository interfaces

Example structure:

Domain/

Order/
Order.php
OrderId.php
OrderStatus.php
OrderRepository.php

Customer/
Customer.php
CustomerId.php

---

# Application Layer

The Application layer orchestrates domain logic.

Allowed contents:

Commands
Queries
Handlers
DTO
Application services

Example:

Application/

Order/

Command/
CreateOrderCommand.php

Handler/
CreateOrderHandler.php

Query/
GetOrderQuery.php

DTO/
OrderDto.php

---

# Infrastructure Layer

Infrastructure contains integrations and framework code.

Examples:

Doctrine repositories
HTTP clients
Message queues
External services
Cache

Example:

Infrastructure/

Persistence/

Doctrine/

Order/
DoctrineOrderRepository.php

Messaging/

Messenger/

OrderCreatedHandler.php

---

# UI Layer

The UI layer contains entry points.

Examples:

Controllers
CLI commands
HTTP requests
API DTO

Example:

UI/

Http/

Controller/

OrderController.php

Request/

CreateOrderRequest.php

Response/

OrderResponse.php

---

# Dependency Rules

Dependencies must follow this direction:

UI -> Application -> Domain

Infrastructure implements interfaces from Domain or Application.

Domain must not depend on any other layer.

---

# Example Flow

HTTP request

Controller

CreateOrderCommand

CreateOrderHandler

Domain Entity

Repository

Database

---

# Naming

Classes must represent intent.

Good examples:

CreateOrderCommand
CreateOrderHandler
OrderRepository
OrderController

Bad examples:

OrderService
OrderManager
Helper
Util

---

# Directory Organization

Group code by domain instead of technical layers when possible.

Example:

Domain/

Order/
Customer/
Payment/

Not:

Domain/

Entity/
ValueObject/
Service/

---

# DTO Location

DTO must belong to Application layer or UI layer.

Never place DTO in Domain.

---

# Entity Isolation

Entities must not contain:

HTTP logic
ORM annotations related to infrastructure
serialization logic

Entities represent business rules only.
