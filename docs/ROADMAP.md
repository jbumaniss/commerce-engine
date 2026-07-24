# Commerce Engine — Roadmap

Compact ticket roadmap for the Catalog/Product API. Status: `Completed` / `In progress` /
`Planned`. Kept intentionally small — extend it as real tickets are defined, not speculatively.

| Ticket | Title | Status | Scope | Endpoint | Depends on |
|--------|-------|--------|-------|----------|------------|
| CE-001 | Project setup | Completed | Symfony, Docker, baseline structure | — | — |
| CE-002 | Product domain | Completed | `Product` entity and persistence | — | CE-001 |
| CE-003 | Create product | Completed | Create a product | `POST /api/products` | CE-002 |
| CE-004 | Validation / supporting setup | Completed | Project-specific quality/validation setup | — | CE-003 |
| CE-005 | Get product | Completed | Fetch one product; 404 when missing | `GET /api/products/{id}` | CE-003 |
| CE-006 | List products | Completed | List active products | `GET /api/products` | CE-003 |
| CE-007 | Update product | In progress | Full replace of editable fields (name, slug, priceAmount, currency, description); `isActive` unchanged | `PUT /api/products/{id}` | CE-005 |
| CE-008 | Activate/deactivate product | Planned | Product status transitions (reuses `Product::activate()`/`deactivate()`) | status endpoints | CE-005 |
| CE-009 | Delete product | Planned | Remove a product | `DELETE /api/products/{id}` | CE-005 |
| CE-010 | Product API cleanup | Planned | Shared response mapping, error shape, edge cases | — | CE-007, CE-008, CE-009 |

## Conventions

- Architecture: **Query/Command → Handler → Controller → Repository**, one write DTO per
  write endpoint. See existing Catalog code for the canonical pattern.
- Response shape is consistent across all product endpoints (`id`, `name`, `slug`,
  `priceAmount`, `currency`, `description`, `isActive`, `createdAt`, `updatedAt`).
