# Практика DDD/CQRS: доменний шар для Symfony `warehouses`

## Контекст (навіщо це)

Теорію DDD/CQRS вже прочитано й зрозуміло — бракує **практичної навички**, тобто
м'язової пам'яті на повний вертикальний зріз. Її не «вичитують», її набивають
повтором: руками будуєш агрегат → VO → подію → репозиторій → тест, з негайним
фідбеком.

Формат: **парне програмування** — я веду й пояснюю кожне рішення (чому VO, чому
подія саме тут, де межа агрегату, де живе інваріант), ти пишеш код під коментар.
Полігон: **`warehouses` (Symfony)** — єдиний сервіс без доменного шару, тож вправа
реалістична (brownfield-DDD: витягнути домен з анемічних Doctrine-сутностей), а не
«ще один CRUD».

Мета сесії — дати `ProductReservation` справжній доменний шар, дзеркалячи готовий
Laravel-еталон, і **перенести бізнес-правила з обробників в агрегат**.

## Стан «до» (що рефакторимо)

- `warehouses/src/Entity/ProductReservation.php` — анемічна сутність: статус як
  `string` + константи, публічний `setStatus()` дозволяє будь-який перехід, жодних
  інваріантів, жодних доменних подій.
- `warehouses/src/Application/Command/CommitReservation/CommitReservationHandler.php`
  та `.../CancelReservation/CancelReservationHandler.php` — правила переходів
  (`status === PENDING`, «лише pending можна скасувати») зашиті **в обробнику**,
  статус міняється сирим `setStatus()`. Це і є «анемічна модель».
- У `warehouses` **немає** `src/Domain`, немає Value Objects, немає базового
  агрегату, немає юніт-тестів домену (лише API-тести).

## Еталон, який дзеркалимо (Laravel, вже готовий)

- `shop-2-back/app/Domain/Shared/AggregateRoot.php` — база з `recordEvent()` /
  `pullEvents()`.
- `shop-2-back/app/Domain/Order/Order.php` — приватний конструктор, статичні
  `create()` + `reconstitute()`, методи поведінки (`markAsPaid()`) які
  **перевіряють інваріант і записують подію**.
- `shop-2-back/app/Domain/Order/OrderStatus.php` — enum-VO зі
  `allowedTransitions()` / `canTransitionTo()` (стейт-машина в типі).
- `shop-2-back/app/Domain/Shared/ValueObjects/Quantity.php` — шаблон VO (валідація
  в конструкторі, незмінність).
- Мапер + репозиторій: `EloquentOrderRepository` / `OrderMapper`
  (`toDomain()` / `toPersistence()`) під
  `shop-2-back/app/Infrastructure/Persistence/Order/`.

## Цільова структура (нове в `warehouses/src`)

```
src/Domain/
├── Shared/
│   └── AggregateRoot.php              # копія Laravel-бази (recordEvent/pullEvents)
└── Reservation/
    ├── Reservation.php                # агрегат: private ctor, create()/reconstitute(),
    │                                   #   commit(), cancel(string $comment)
    ├── ReservationStatus.php          # enum-VO: Pending/Committed/Canceled +
    │                                   #   allowedTransitions()/canTransitionTo()
    ├── ReservationItem.php            # елемент усередині агрегату (Sku + Quantity)
    ├── ReservationRepository.php      # доменний інтерфейс (контракт)
    ├── ValueObjects/
    │   ├── ReservationId.php
    │   ├── Sku.php
    │   └── Quantity.php               # дзеркало Laravel Quantity
    └── Events/
        ├── ReservationCommitted.php
        └── ReservationCanceled.php

src/Infrastructure/Persistence/Reservation/
├── DoctrineReservationRepository.php  # implements ReservationRepository
└── ReservationMapper.php              # Doctrine entity ↔ domain aggregate
```

`ProductReservation` (Doctrine) **залишається** як запис персистенції — рівно як
Laravel тримає Eloquent-модель окремо від доменного агрегату. Домен не знає про
Doctrine; міст — мапер.

## Парна сесія (покроково; я пояснюю — ти пишеш)

1. **База.** `Domain/Shared/AggregateRoot.php` — копія Laravel-версії.
   *Концепт:* агрегат накопичує події, не диспетчерить їх сам.
2. **Статус як тип.** `ReservationStatus` enum-VO з `canTransitionTo()` (дзеркало
   `OrderStatus`). *Концепт:* недозволений перехід стає неможливим у типі, а не
   ловиться `if`-ом в обробнику.
3. **Value Objects.** `ReservationId`, `Sku`, `Quantity` — валідація в
   конструкторі, незмінність. *Концепт:* первинні `string`/`int` ховаємо за типами.
4. **Агрегат.** `Reservation` з приватним ctor, `create()`, `reconstitute()`,
   та методами `commit()` / `cancel(string $comment)`, що **переносять інваріанти
   з обробників**: лише `Pending` комітиться/скасовується, інакше `DomainException`;
   кожен метод записує подію (`ReservationCommitted` / `ReservationCanceled`).
   *Концепт:* правила живуть в агрегаті, поведінка замість сеттерів.
5. **Репозиторій + мапер.** Інтерфейс `ReservationRepository`, реалізація
   `DoctrineReservationRepository` + `ReservationMapper` (`toDomain`/`toPersistence`).
   *Концепт:* інверсія залежностей — домен залежить від інтерфейсу, не від Doctrine.
6. **Підключення CQRS.** Переписати `CommitReservationHandler` /
   `CancelReservationHandler`: завантажити агрегат через репозиторій, викликати
   `commit()`/`cancel()`, зберегти, віддиспетчити `pullEvents()`.
   `StockAvailabilityService` лишається (побічний ефект на залишки).
   *Концепт:* обробник — тонкий оркестратор, а не носій правил.
7. **Тести домену.** `tests/Unit/Domain/Reservation/` — дзеркало
   `OrderTest`/`OrderStatusTest`: успішний commit, заборона подвійного commit,
   скасування лише pending, запис подій, валідація VO. Це і є safety-net.

**Точка для обговорення по ходу:** у `warehouses` вже є Symfony Workflow
(`marking`, `ProductReservationTransitionProcessor`) для HTTP-переходів через
API Platform. Розберемо, де тоді живе інваріант: Workflow лишаємо для HTTP-шляху,
домен володіє правилами на командному шляху — і чому це нормально (один інваріант,
дві точки входу).

## Перевірка (end-to-end)

- Юніт-тести домену: `php bin/phpunit tests/Unit/Domain/Reservation`
  (запускати з `warehouses/`). Спершу перевірити, що PHPUnit доступний.
- Існуючий `warehouses/tests/ProductReservationApiTest.php` має лишитися зеленим —
  доводить, що рефакторинг не зламав HTTP-поведінку.
- Ручний прогін командного шляху: створити резервацію → `CommitReservationCommand`
  → переконатися, що статус `committed`, залишки списані, повторний commit
  ідемпотентний (без винятку).

## Як це дає навичку

Один зріз робиться зі мною (кроки 1–7). Далі — **повтор без мене**: тим самим
шаблоном самостійно даси доменний шар сусідньому агрегату (`InventoryTransaction`
або `StockAvailability`), а я зроблю лише фінальний код-рев'ю проти еталона.
Саме другий, самостійний прохід перетворює «зрозумів» на «вмію».
