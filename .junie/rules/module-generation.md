# Module Generation Guidelines

When creating a new feature, generate a full module structure.

Modules must follow DDD structure.

Example module: Order

---

Domain

src/Domain/Order/

Order.php
OrderId.php
OrderStatus.php
OrderRepository.php
OrderCreatedEvent.php

---

Application

src/Application/Order/

Command/
CreateOrderCommand.php

Handler/
CreateOrderHandler.php

Query/
GetOrderQuery.php

DTO/
OrderDto.php

---

Infrastructure

src/Infrastructure/Persistence/Doctrine/Order/

DoctrineOrderRepository.php

---

UI

src/UI/Http/Controller/

OrderController.php

src/UI/Http/Request/

CreateOrderRequest.php

src/UI/Http/Response/

OrderResponse.php

---

Rules

A feature must not be implemented as a single class.

Always generate a full module structure.
