<?php

declare(strict_types=1);

namespace App\Contracts\Subscriber;

interface HasPreferences
{
    public function getPreferredFrequency(): string;

    /**
     * @return list<string>
     */
    public function getPreferredCategories(): array;
}
