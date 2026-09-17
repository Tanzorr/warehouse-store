---
description: "Use when writing Symfony code in warehouses: entities, DTOs, repositories, services, controllers, API Platform resources, validators, event subscribers, or tests."
applyTo: "warehouses/**"
---

# Symfony / API Platform (warehouses) — Best Practices

## Architecture

- **Entities** (`src/Entity/`) — Doctrine ORM mapped classes with `#[ApiResource]` when exposed via API
- **DTOs** (`src/DTO/`) — **mandatory** for all write operations (POST/PUT/PATCH); never accept raw entity data from input
- **Repositories** (`src/Repository/`) — extend `ServiceEntityRepository`; complex queries belong here, not in services
- **Services** (`src/Service/`) — business logic; inject via constructor (autowiring)
- **Validators** (`src/Validator/`) — custom constraint classes for domain-specific validation
- **EventSubscribers** (`src/EventSubscriber/`) — cross-cutting concerns (logging, auth checks, etc.)

## Entities

```php
#[ApiResource]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;
}
```

- All properties must have `#[Assert\...]` constraints where applicable
- Use `#[ORM\HasLifecycleCallbacks]` for `createdAt`/`updatedAt` auto-fill
- Prefer `\DateTimeImmutable` over `\DateTime`
- Never expose entity setters directly through API — use DTOs

## DTOs

- Use `#[Assert\...]` constraints on all DTO properties
- Use `#[Assert\Valid]` on nested object properties to trigger cascade validation
- Constructor should set sensible defaults if needed
- One DTO per operation — do not reuse input DTOs for different operations

## API Platform

- Use PHP 8 attributes (`#[ApiResource]`, `#[ApiProperty]`, etc.) — not YAML/XML
- Define `operations` explicitly when not all CRUD is needed
- For custom operations, use `#[ApiResource(operations: [new Post(processor: ...), ...])]`
- Use `StateProcessor` / `StateProvider` for custom business logic on endpoints

## Static Analysis

- Psalm errorLevel **3** — do not decrease
- Run before committing: `vendor/bin/psalm`
- Run tests: `php bin/phpunit`

## Authentication

- JWT auth via `lexik/jwt-authentication-bundle` — no session-based auth
- Never store sensitive data in tokens
- Use Symfony Security roles (`ROLE_USER`, `ROLE_ADMIN`) for access control on API operations

## Migrations

- Always generate migrations after entity changes: `php bin/console doctrine:migrations:diff`
- Never edit already-executed migrations — create a new one
- Run: `php bin/console doctrine:migrations:migrate`

## Symfony Workflow

- Use `symfony/workflow` for state machine transitions (e.g., reservation status)
- Define transitions in `config/packages/workflow.yaml`
- Guard transitions via `EventSubscriber` — never skip the workflow to set state directly
