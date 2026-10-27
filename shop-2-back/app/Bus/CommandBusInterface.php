<?php

namespace App\Bus;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;
}
