# Commerce Engine — Roadmap

One continuous, sequentially-numbered backlog for a Symfony Commerce Engine, ordered so that
later tickets depend only on earlier ones, ending at a production-ready **Commerce Engine v1.0**.

**Legend:** `Completed` = built & verified · `In Progress` = actively being implemented · `Planned` = not yet started.

## Roadmap Progress

- Completed: 12
- In Progress: 1 (CE-013)
- Planned: 123
- Current Ticket: CE-013

Operations that must be consistent within a request run **synchronously** in a DB
transaction; eventually-consistent side effects run **asynchronously** via Symfony Messenger
and are marked *(async)*. Messaging is **transport-agnostic**: Redis for local/dev/test and an
SQS-compatible transport in production, interchangeable via configuration only.

| Ticket | Title | Status | Description | Endpoint(s) | Depends on |
|--------|-------|--------|-------------|-------------|------------|
| CE-001 | Foundation: Project setup | Completed | Symfony, Docker, baseline structure. | — | — |
| CE-002 | Product: Domain & persistence | Completed | `Product` entity and persistence. | — | CE-001 |
| CE-003 | Product: Create | Completed | Create a product. | `POST /api/products` | CE-002 |
| CE-004 | Foundation: Validation & tooling | Completed | Project-specific quality/validation setup. | — | CE-003 |
| CE-005 | Product: Get by ID | Completed | Fetch one product; 404 when missing. | `GET /api/products/{id}` | CE-003 |
| CE-006 | Product: List | Completed | List active products. | `GET /api/products` | CE-003 |
| CE-007 | Product: Update | Completed | Full replace of editable fields (name, slug, priceAmount, currency, description); `isActive` unchanged. | `PUT /api/products/{id}` | CE-005 |
| CE-008 | Product: Activate/deactivate | Completed | Product status transitions (reuses `Product::activate()`/`deactivate()`). | `POST /api/products/{id}/activate`, `POST /api/products/{id}/deactivate` | CE-005 |
| CE-009 | Product: Delete | Completed | Hard delete a product; 204 on success, 404 when missing. | `DELETE /api/products/{id}` | CE-005 |
| CE-010 | Product: API cleanup | Completed | Shared `ProductResponse` mapper; duplicate-slug → 409 on Create/Update. | — | CE-007, CE-008, CE-009 |
| CE-011 | API: Error format consistency | Completed | RFC 7807 `application/problem+json` for all `/api/` errors (404/409/422/5xx) via a shared exception listener. | — | CE-010 |
| CE-012 | Product: List pagination | Completed | `page`/`perPage` (validated) with `items`/`page`/`perPage`/`total`/`totalPages` metadata; `id ASC` order. | `GET /api/products` | CE-006 |
| CE-013 | Product: List filtering & sorting | In Progress | Optional filters (name/slug/isActive) and client-selectable sort on the paginated list. | `GET /api/products` | CE-012 |
| CE-014 | Product: Optimistic locking | Planned | Add a Doctrine version field to `Product`; a concurrent update returns 409 Conflict. | `PUT /api/products/{id}` | CE-007 |
| CE-015 | Product: Read caching | Planned | HTTP caching (ETag/Last-Modified) for product reads, invalidated on writes. | `GET /api/products`, `GET /api/products/{id}` | CE-010 |
| CE-016 | Product: OpenAPI specification | Planned | Document every product endpoint (schemas, params, error shapes) as OpenAPI. | — | CE-013 |
| CE-017 | Product: Regression suite | Planned | End-to-end functional and contract tests guarding the product API surface. | — | CE-013 |
| CE-018 | Messaging: Symfony Messenger introduction | Planned | Install Messenger and route existing writes through a synchronous command bus. | — | CE-010 |
| CE-019 | Messaging: Command & event bus separation | Planned | Separate a synchronous command bus from an asynchronous event bus, following CQRS conventions. | — | CE-018 |
| CE-020 | Messaging: Redis transport (local/dev) | Planned | Configure a Redis Messenger transport for local, dev and test async processing. | — | CE-019 |
| CE-021 | Messaging: Transport compatibility (Redis ↔ SQS) | Planned | Keep transport routing env-driven and transport-agnostic so Redis and an SQS-compatible transport are interchangeable via configuration, with no application code change. | — | CE-020 |
| CE-022 | Messaging: Retry strategy | Planned | Configure per-transport retry policies (attempts, backoff) for transient failures. | — | CE-021 |
| CE-023 | Messaging: Dead Letter Queue | Planned | Route messages that exhaust retries to a failed (dead-letter) transport. | — | CE-022 |
| CE-024 | Messaging: Failed message handling | Planned | Inspect, retry and remove failed messages; operational tooling around the DLQ. | — | CE-023 |
| CE-025 | Messaging: Idempotency | Planned | Make async handlers idempotent/deduplicated so redelivered messages are safe. | — | CE-019 |
| CE-026 | Notifications: Async email dispatch | Planned | Send emails through a mailer abstraction, dispatched asynchronously on the event bus. | — | CE-019, CE-021 |
| CE-027 | Notifications: Email templates | Planned | Reusable, localisable transactional email templates rendered from domain events. | — | CE-026 |
| CE-028 | Notifications: Notification module | Planned | Channel-agnostic notification abstraction (email now, extensible later) driven by events. | — | CE-026 |
| CE-029 | Messaging: Worker monitoring | Planned | Supervisor-managed workers plus Horizon-equivalent monitoring, metrics and health for consumers. | — | CE-020 |
| CE-030 | Brand: Domain | Planned | Introduce a `Brand` bounded context (entity, persistence, invariants). | — | CE-002 |
| CE-031 | Brand: CRUD API | Planned | Create, read, update, delete and list brands. | `/api/brands` | CE-030 |
| CE-032 | Brand: Product relationship | Planned | Associate each product with an optional brand, with migration and integrity. | — | CE-030, CE-007 |
| CE-033 | Category: Domain | Planned | `Category` aggregate supporting a parent/child hierarchy. | — | CE-002 |
| CE-034 | Category: CRUD API | Planned | Manage categories, including hierarchy operations. | `/api/categories` | CE-033 |
| CE-035 | Category: Product relationship | Planned | Many-to-many product/category association with migration and integrity. | — | CE-033, CE-007 |
| CE-036 | Attribute: Domain | Planned | Define product attributes (e.g. colour, size) as reusable definitions. | — | CE-002 |
| CE-037 | Attribute: CRUD API | Planned | Manage attribute definitions. | `/api/attributes` | CE-036 |
| CE-038 | Attribute: Values | Planned | Manage the allowed values for each attribute. | `/api/attributes/{id}/values` | CE-036 |
| CE-039 | Product: Attribute assignment | Planned | Assign attribute values to products. | `/api/products/{id}/attributes` | CE-037, CE-038 |
| CE-040 | Product: Variants | Planned | Model variants (a SKU per attribute-value combination) under a product. | `/api/products/{id}/variants` | CE-039 |
| CE-041 | Product: Variant pricing & SKU | Planned | Per-variant SKU, price and status overrides. | — | CE-040 |
| CE-042 | Media: Domain | Planned | Media asset entity backed by a filesystem/storage abstraction. | — | CE-001 |
| CE-043 | Media: Upload API | Planned | Upload and manage media assets through the storage abstraction. | `/api/media` | CE-042 |
| CE-044 | Media: Product media management | Planned | Attach and order media/galleries on products and variants. | `/api/products/{id}/media` | CE-043, CE-040 |
| CE-045 | Product: Catalog search | Planned | Full-text and faceted search across products by name, brand, category and attributes. | `GET /api/products` | CE-032, CE-035, CE-039 |
| CE-046 | Warehouse: Domain | Planned | Warehouse/location bounded context. | — | CE-001 |
| CE-047 | Warehouse: CRUD API | Planned | Manage warehouses. | `/api/warehouses` | CE-046 |
| CE-048 | Inventory: Domain | Planned | Stock levels per SKU/variant per warehouse, independent from the catalog. | — | CE-040, CE-046 |
| CE-049 | Inventory: Stock adjustments & ledger | Planned | Transactional stock changes with reasons and an append-only movement ledger. | `/api/inventory` | CE-048 |
| CE-050 | Inventory: Adjustment events (async) | Planned | Publish stock-change domain events on the event bus for downstream consumers. | — | CE-019, CE-049 |
| CE-051 | Inventory: Stock reservations | Planned | Reserve stock synchronously for pending orders, preventing oversell via optimistic locking. | — | CE-048 |
| CE-052 | Inventory: Reservation release (async) | Planned | Release expired or cancelled reservations through a Messenger consumer. | — | CE-019, CE-051 |
| CE-053 | Inventory: Low-stock notifications (async) | Planned | Emit low-stock events that trigger asynchronous notifications. | — | CE-028, CE-050 |
| CE-054 | Supplier: Domain | Planned | Supplier bounded context. | — | CE-001 |
| CE-055 | Supplier: CRUD API | Planned | Manage suppliers. | `/api/suppliers` | CE-054 |
| CE-056 | Purchasing: Purchase order domain | Planned | Purchase-order aggregate (supplier, lines, status). | — | CE-054, CE-048 |
| CE-057 | Purchasing: Purchase order API | Planned | Create and receive purchase orders; receiving increments warehouse stock transactionally. | `/api/purchase-orders` | CE-056, CE-049 |
| CE-058 | Customer: Aggregate | Planned | Customer bounded context (identity, contact, status). | — | CE-002 |
| CE-059 | Customer: CRUD API | Planned | Manage customers, consistent with catalog conventions. | `/api/customers` | CE-058 |
| CE-060 | Customer: Addresses | Planned | Billing and shipping addresses on the customer aggregate. | `/api/customers/{id}/addresses` | CE-058 |
| CE-061 | Customer: Groups | Planned | Customer groups (e.g. wholesale) with membership, used for pricing. | `/api/customer-groups` | CE-058 |
| CE-062 | Customer: Search | Planned | Filter, sort and paginate customers, reusing the pagination pattern. | `GET /api/customers` | CE-059, CE-012 |
| CE-063 | Pricing: Price lists | Planned | Price model with currency and price lists per customer group. | — | CE-041, CE-061 |
| CE-064 | Tax: Rates & rules | Planned | Tax rates and rules by region/category. | `/api/taxes` | CE-001 |
| CE-065 | Tax: Calculation | Planned | Apply tax rules to prices and order totals. | — | CE-064 |
| CE-066 | Promotions: Discounts | Planned | Reusable discount rule abstraction (fixed/percentage, scope). | — | CE-063 |
| CE-067 | Promotions: Promotion engine | Planned | Cart- and catalog-level promotion rules built on discounts. | `/api/promotions` | CE-066 |
| CE-068 | Promotions: Coupons | Planned | Coupon entities with validation and redemption tied to promotions. | `/api/coupons` | CE-067 |
| CE-069 | Cart: Domain | Planned | Cart aggregate with items for guest and known customers. | — | CE-041, CE-058 |
| CE-070 | Cart: API | Planned | Add, update, remove items and read the cart. | `/api/carts`, `/api/carts/{id}/items` | CE-069 |
| CE-071 | Cart: Pricing | Planned | Apply pricing, tax, promotions and coupons to compute cart totals. | — | CE-063, CE-065, CE-068, CE-070 |
| CE-072 | Order: Aggregate & items | Planned | `Order` and `OrderItem` aggregate capturing price snapshots at purchase time. | — | CE-058, CE-041 |
| CE-073 | Order: Totals | Planned | Compute subtotal, tax, discount and grand total as domain logic. | — | CE-072, CE-071 |
| CE-074 | Checkout: Checkout flow | Planned | Synchronously and transactionally convert a cart into an order, reserving stock and computing totals. | `POST /api/checkout` | CE-051, CE-071, CE-073 |
| CE-075 | Order: Status workflow | Planned | Guarded order state machine (pending → paid → fulfilled → …). | `/api/orders/{id}/status` | CE-072 |
| CE-076 | Order: Confirmation processing (async) | Planned | On order placement, dispatch confirmation email and downstream events. | — | CE-026, CE-074 |
| CE-077 | Order: Cancellation (async) | Planned | Cancel an order and release its stock reservations via Messenger. | `POST /api/orders/{id}/cancel` | CE-052, CE-075 |
| CE-078 | Order: History & events | Planned | Persist and expose the order lifecycle event log. | `GET /api/orders/{id}/history` | CE-075 |
| CE-079 | Order: Orders API | Planned | List, filter, paginate and read orders. | `/api/orders`, `/api/orders/{id}` | CE-072, CE-012 |
| CE-080 | Payment: Provider abstraction | Planned | Provider-agnostic payment port (authorize, capture, refund). | — | CE-074 |
| CE-081 | Payment: Provider adapter | Planned | Reference adapter implementing the port with no vendor lock-in. | — | CE-080 |
| CE-082 | Payment: Capture flow | Planned | Create and capture a payment for an order, advancing its status. | `POST /api/orders/{id}/payments` | CE-081, CE-075 |
| CE-083 | Payment: Webhooks (async) | Planned | Ingest provider callbacks and process them idempotently via Messenger. | `POST /api/payments/webhook` | CE-025, CE-081 |
| CE-084 | Payment: Refunds | Planned | Full and partial refunds tied to order and payment state. | `POST /api/orders/{id}/refund` | CE-082 |
| CE-085 | Payment: Failure handling | Planned | Retries, failure states and reconciliation with the order workflow. | — | CE-022, CE-083 |
| CE-086 | Shipping: Provider abstraction | Planned | Provider-agnostic shipping port. | — | CE-074 |
| CE-087 | Shipping: Methods & rates | Planned | Shipping-method selection and rate calculation. | `/api/shipping/methods` | CE-086 |
| CE-088 | Shipping: Shipment aggregate | Planned | Shipment entity created from an order. | `/api/shipments` | CE-075, CE-087 |
| CE-089 | Shipping: Labels | Planned | Generate shipping labels through the provider port. | `POST /api/shipments/{id}/label` | CE-088, CE-086 |
| CE-090 | Shipping: Tracking & states | Planned | Track shipments and transition delivery states. | `GET /api/shipments/{id}` | CE-088 |
| CE-091 | Shipping: Notifications (async) | Planned | Delivery-state changes trigger asynchronous customer notifications. | — | CE-028, CE-090 |
| CE-092 | Settings: Settings module | Planned | Typed key/value application settings with runtime access. | `/api/settings` | CE-001 |
| CE-093 | Admin: Feature flags | Planned | Toggle features at runtime, evaluated across the application. | `/api/feature-flags` | CE-092 |
| CE-094 | Audit: Audit log (async) | Planned | Asynchronous, append-only audit trail of state-changing actions. | `GET /api/audit-logs` | CE-019 |
| CE-095 | Admin: Dashboard API | Planned | Aggregated metrics (sales, orders, stock) for an admin dashboard. | `/api/admin/dashboard` | CE-079, CE-049 |
| CE-096 | Observability: Metrics & instrumentation | Planned | Expose application and business metrics in a Prometheus-compatible format. | `/metrics` | CE-001 |
| CE-097 | Observability: Structured logging | Planned | Structured, correlated logging across HTTP requests and workers. | — | CE-001 |
| CE-098 | Security: Authentication | Planned | Token/JWT authentication for API clients. | `/api/auth/login` | CE-011 |
| CE-099 | Security: Authorization & roles | Planned | Role-based access control (RBAC) with role assignment. | — | CE-098 |
| CE-100 | Security: Permissions & voters | Planned | Fine-grained permissions enforced by voters on state-changing endpoints. | — | CE-099 |
| CE-101 | Security: API keys | Planned | API-key authentication for machine-to-machine clients. | `/api/api-keys` | CE-098 |
| CE-102 | Security: Rate limiting | Planned | Apply Symfony RateLimiter to public and authentication endpoints. | — | CE-098 |
| CE-103 | Security: Hardening | Planned | Security headers, CORS, input hardening and secrets management. | — | CE-100 |
| CE-104 | Testing: Integration suite | Planned | Persistence and messaging integration tests against real Redis/DB. | — | CE-021 |
| CE-105 | Testing: Functional API suite | Planned | End-to-end HTTP functional tests across every backend module. | — | CE-079, CE-082, CE-088 |
| CE-106 | Testing: End-to-end backend | Planned | Full purchase-flow scenarios (cart → checkout → pay → ship). | — | CE-084, CE-090 |
| CE-107 | Docs: Full OpenAPI documentation | Planned | Complete, published OpenAPI specification across all backend modules. | — | CE-105 |
| CE-108 | Docs: Developer & operations | Planned | Setup guides and runbooks, including worker/DLQ operations and the transport-switch procedure. | — | CE-029, CE-105 |
| CE-109 | Tooling: Local development | Planned | Developer experience: make targets, seed data, fixtures and refined Docker Compose. | — | CE-001 |
| CE-110 | Frontend: Project setup | Planned | Scaffold the admin frontend with Vue 3, TypeScript and Vite, including routing and state management. | — | CE-107 |
| CE-111 | Frontend: API client & types | Planned | Typed frontend API client aligned with the OpenAPI specification. | — | CE-107, CE-110 |
| CE-112 | Frontend: Authentication | Planned | Login, token handling and route guards in the frontend. | — | CE-098, CE-111 |
| CE-113 | Frontend: App shell & layout | Planned | Navigation, layout, and shared loading/error states and design-system base. | — | CE-110 |
| CE-114 | Frontend: Product management UI | Planned | Manage products, variants, attributes and media from the UI. | — | CE-111, CE-113 |
| CE-115 | Frontend: Category & brand UI | Planned | Manage categories (hierarchy) and brands. | — | CE-114 |
| CE-116 | Frontend: Attribute UI | Planned | Manage attributes and their values. | — | CE-114 |
| CE-117 | Frontend: Inventory UI | Planned | View and adjust stock, warehouses and reservations. | — | CE-114 |
| CE-118 | Frontend: Customer UI | Planned | Manage customers, addresses and groups. | — | CE-113, CE-111 |
| CE-119 | Frontend: Order UI | Planned | Order list/detail, status transitions, refunds and shipments. | — | CE-118, CE-114 |
| CE-120 | Frontend: Cart & checkout UI | Planned | Cart management and checkout flow for admin/testing use. | — | CE-114, CE-118 |
| CE-121 | Frontend: Dashboard UI | Planned | KPI/metrics dashboard consuming the admin dashboard API. | — | CE-119, CE-095 |
| CE-122 | Frontend: Settings & feature-flags UI | Planned | Manage application settings and feature flags. | — | CE-113, CE-092 |
| CE-123 | Frontend: Component tests | Planned | Vitest unit/component tests for the UI. | — | CE-114 |
| CE-124 | Frontend: Playwright E2E tests | Planned | End-to-end browser tests for authentication, product, customer, order and checkout flows. | — | CE-119, CE-120 |
| CE-125 | Performance: Application caching | Planned | Redis-backed cache pool for hot reads with explicit invalidation. | — | CE-020 |
| CE-126 | Performance: Optimisation | Planned | Query/index tuning, N+1 elimination and load-test-driven improvements across backend and frontend. | — | CE-105, CE-124 |
| CE-127 | Architecture: Transactions & consistency review | Planned | Audit transactional boundaries and optimistic-locking coverage across modules. | — | CE-074 |
| CE-128 | Observability: Health checks | Planned | Liveness/readiness endpoints covering app, database, Redis and workers. | `/health` | CE-096 |
| CE-129 | Observability: Monitoring | Planned | Tracing, metric dashboards and log aggregation with alerting. | — | CE-096, CE-097 |
| CE-130 | Observability: Error handling & reporting | Planned | Global error reporting/aggregation integration for API, workers and frontend. | — | CE-011, CE-124 |
| CE-131 | CI/CD: Pipeline | Planned | Continuous integration running lint, static analysis and the full backend + frontend test suites on every push. | — | CE-106, CE-124 |
| CE-132 | CI/CD: Deployment | Planned | Build/release pipeline with migrations and zero-downtime deployment of backend and frontend. | — | CE-131 |
| CE-133 | Security: Dependency & security audit | Planned | Full authorization review, dependency/CVE and license scanning, and penetration-test review. | — | CE-103, CE-124 |
| CE-134 | Production: Configuration & checklist | Planned | Production environment config, backups, scaling policy, and a signed-off readiness checklist. | — | CE-132, CE-128, CE-129, CE-133 |
| CE-135 | Production: Release candidate | Planned | Feature freeze and full-stack regression across backend and frontend, producing the v1.0 release candidate. | — | CE-134, CE-124 |
| CE-136 | Production: Commerce Engine v1.0 | Planned | Production-ready release: catalog, inventory, customers, cart, orders, payments, shipping, notifications, async processing, admin, security, observability, documentation, tests, CI/CD and the Vue frontend — audited, hardened, tagged and deployed as v1.0. | — | CE-135 |

## Project Outcome

On completion, the Commerce Engine is a **production-ready portfolio application** demonstrating
senior/lead-level Symfony architecture (DDD, CQRS, Symfony Messenger), comprehensive testing
(unit, integration, functional, end-to-end), security (authentication, authorization, hardening,
audit), a Vue 3 + TypeScript frontend with Playwright E2E coverage, CI/CD and deployment, and
operational practices — observability, transport-agnostic asynchronous processing, and a
signed-off production-readiness checklist.
