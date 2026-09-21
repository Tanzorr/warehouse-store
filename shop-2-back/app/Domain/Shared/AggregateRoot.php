<?php

namespace App\Domain\Shared;

abstract class AggregateRoot
{
    /** @var object[] */
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return object[] */
    public function pullEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
