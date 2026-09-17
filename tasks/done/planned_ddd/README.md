# Плани імплементації — Shop & Warehouse DDD/CQRS

## Мета проекту
Зрозуміти та практично застосувати патерни **Domain-Driven Design (DDD)** та
**Command Query Responsibility Segregation (CQRS)** через реальний проект
магазину (Laravel) + складу (Symfony) + адмін-панелі (Angular).

---

## Карта планів

| # | Файл | Що реалізує | Технологія |
|---|------|-------------|------------|
| 01 | [01-laravel-command-query-bus.md](./01-laravel-command-query-bus.md) | Command Bus + Query Bus | Laravel |
| 02 | [02-laravel-value-objects.md](./02-laravel-value-objects.md) | Value Objects (Money, Sku, OrderId…) | Laravel |
| 03 | [03-laravel-aggregate-roots.md](./03-laravel-aggregate-roots.md) | Aggregate Root + Domain Events | Laravel |
| 04 | [04-laravel-repository-pattern.md](./04-laravel-repository-pattern.md) | Repository Interface + Eloquent Impl | Laravel |
| 05 | [05-warehouse-cqrs-commands.md](./05-warehouse-cqrs-commands.md) | CancelReservation, AdjustStock, TransferStock | Symfony |
| 06 | [06-warehouse-cqrs-queries.md](./06-warehouse-cqrs-queries.md) | GetStockLevels, GetReservations Query side | Symfony |
| 07 | [07-angular-admin-orders.md](./07-angular-admin-orders.md) | Адмін: управління замовленнями | Angular |
| 08 | [08-angular-admin-products.md](./08-angular-admin-products.md) | Адмін: управління продуктами та категоріями | Angular |
| 09 | [09-angular-admin-warehouse.md](./09-angular-admin-warehouse.md) | Адмін: перегляд стоку та резервувань | Angular |
| 10 | [10-angular-admin-auth.md](./10-angular-admin-auth.md) | Login сторінка, AuthService, interceptor, оновлений admin guard | Angular |
| 11 | [11-shared-jwt-auth.md](./11-shared-jwt-auth.md) | Спільний JWT (HS256) — Laravel видає, Laravel + Symfony валідують | Laravel + Symfony |

---

## Рекомендований порядок виконання

```
[01] Command/Query Bus       ← фундамент для всього Laravel
        ↓
[02] Value Objects           ← типобезпечні дані в командах
        ↓
[03] Aggregate Roots         ← бізнес-логіка і domain events
        ↓
[04] Repository Pattern      ← ізоляція persistence від домену
        ↓
[05] Warehouse Commands      ← розширення Symfony CQRS
        ↓
[06] Warehouse Queries       ← read-side в Symfony
        ↓
[07] Angular Admin Orders    ← UI для команд (write side)
        ↓
[08] Angular Admin Products  ← UI для CRUD через Command Bus
        ↓
[09] Angular Admin Warehouse ← UI для query side складу
        ↓
[10] Angular Admin Auth       ← login, AuthService, interceptor, guard
        ↓
[11] Shared JWT (Laravel ↔ Symfony) ← один токен визнають обидва бекенди
                                       (передумова для реальної перевірки 07–09 через API)
```

---

## Ключові концепції які відпрацьовуються

### CQRS
- **Command** — намір змінити стан (CreateProduct, PayOrder). Не повертає дані.
- **Query** — запит на читання (GetProducts, GetOrdersByStatus). Не змінює стан.
- **CommandBus** — маршрутизує Command → Handler.
- **QueryBus** — маршрутизує Query → Handler.

### DDD
- **Aggregate Root** — кластер об'єктів з одним входом (Order керує OrderItems).
- **Value Object** — незмінний об'єкт без identity (Money, Sku, Address).
- **Domain Event** — факт що стався в домені (OrderPaid, ProductCreated).
- **Repository** — абстракція для persistence (зберігає/знаходить Aggregates).
- **Bounded Context** — межі де модель має конкретне значення (Shop vs Warehouse).
