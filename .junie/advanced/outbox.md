# Outbox Pattern

Never publish integration events directly after DB commit.

Instead:

1 save aggregate
2 save event to outbox table
3 background worker publishes events
