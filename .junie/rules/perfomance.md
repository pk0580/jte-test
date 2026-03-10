# Performance Guidelines

Assume large datasets.

Avoid:

- loading entire tables
- heavy joins without indexes

Prefer:

pagination
streaming
batch processing

Heavy operations must run asynchronously.
