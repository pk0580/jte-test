# Idempotency

Commands must be idempotent.

Executing the same command multiple times must not change the result.

Strategies:

unique constraints
status checks
idempotency keys
