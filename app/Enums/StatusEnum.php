<?php

namespace App\Enums;

class StatusEnum
{
    const PAID = 'PAID';
    const UNPAID = 'UNPAID';

    const STATUSES = [
        self::UNPAID,
        self::PAID,
    ];

    public static function list(): array
    {
        return self::STATUSES;
    }
}
