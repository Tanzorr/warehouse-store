# Backlog

This repo's real backlog is [`../../tasks/planned/`](../../tasks/planned/) —
this file is a pointer, not a copy, so it can't go stale by being a second
place to update.

No `TASK-NNN` scheme: each file is named for the feature it describes.

| File | Scope |
| :--- | :--- |
| [`bug-fixes.md`](../../tasks/planned/bug-fixes.md) | Independent fixes found across all three apps, sorted by priority |
| [`order-payment-domain-events.md`](../../tasks/planned/order-payment-domain-events.md) | "Order paid → warehouse confirms reservation" via domain events, queues, CQRS |
| [`order-payment-domain-events.uk.md`](../../tasks/planned/order-payment-domain-events.uk.md) | Ukrainian version of the above |
| [`queue-improvements.md`](../../tasks/planned/queue-improvements.md) | Async/queue gaps in `shop-2-back` and `warehouses` |
| [`queue-improvements.uk.md`](../../tasks/planned/queue-improvements.uk.md) | Ukrainian version of the above |
| [`warehouses-reservation-domain-layer.md`](../../tasks/planned/warehouses-reservation-domain-layer.md) | DDD/CQRS practice: domain layer for `warehouses` reservations |
| [`aws-jenkins-deploy-lab.md`](../../tasks/planned/aws-jenkins-deploy-lab.md) | Ephemeral AWS + Jenkins deploy lab — a learning exercise, not production infra |

Picking up a task: load it in full via [`../router/implement.md`](../router/implement.md).
Writing a new one: no fixed template is enforced — follow the shape of an
existing file close to the new task's scope (`bug-fixes.md` for a small
independent fix, `order-payment-domain-events.md` for a multi-step feature).
