<?php

namespace App\Bus;

interface QueryBusInterface
{
    public function ask(object $query): mixed;
}
