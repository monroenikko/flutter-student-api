<?php

namespace App\Enums;

class BankEnum
{
    const PNB = 'Philippine National Bank';
    const CHINA_BANK = 'China Bank';

    const BANKS = [
        self::CHINA_BANK,
        self::PNB,
    ];

    public static function list(): array
    {
        return self::BANKS;
    }
}
