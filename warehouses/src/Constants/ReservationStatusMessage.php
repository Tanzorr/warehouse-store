<?php

namespace App\Constants;

class ReservationStatusMessage
{
    public const NO_CHANGE       = 'No change needed';
    public const EXPIRED         = 'Expired';
    public const COMMITTED       = 'Committed';
    public const CANCELED        = 'Canceled';
    public const STATUS_UPDATED  = 'Status updated';
    public const STATUS_COMMITTED = 'Status committed';
    public const INVALID_CHANGE  = 'Invalid status change';

    public const NOT_ENOUGH_STOCK = 'Not enough stock';

    public const ERROR_WRONG_STATUS = 'Status must be one of:  Expired, Committed, Pending';

    public const ERROR_WRONG_AMOUNT = "Insufficient stock or invalid quantity specified ({{ value }} / available: {{ available }}).";
    const STATUS_PENDING = 'Pending';
}
