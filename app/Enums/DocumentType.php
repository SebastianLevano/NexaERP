<?php

namespace App\Enums;

enum DocumentType: string
{
    case DNI = 'dni';
    case RUC = 'ruc';
    case CE = 'ce';
    case Passport = 'passport';

    public function label(): string
    {
        return match ($this) {
            self::DNI => 'DNI',
            self::RUC => 'RUC',
            self::CE => 'Carné de Extranjería',
            self::Passport => 'Pasaporte',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::DNI => 'DNI',
            self::RUC => 'RUC',
            self::CE => 'CE',
            self::Passport => 'Pasap.',
        };
    }
}
