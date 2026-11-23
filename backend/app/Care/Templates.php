<?php

namespace App\Care;

class Templates
{
    public const NIGHT_BAG = 'night-bag';

    public function title(string $key): string
    {
        abort_unless($key === self::NIGHT_BAG, 404);
        return 'Nachtzak bij een katheterdagzak';
    }
}
