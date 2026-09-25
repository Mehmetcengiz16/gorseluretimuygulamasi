<?php

namespace App\Support;

class Media
{
    /**
     * Depolanan yolu mutlak URL'ye çevirir. Seed verisindeki uzak görseller (http...) olduğu gibi döner.
     * URL, isteğin geldiği host ile üretilir; böylece emulator (10.0.2.2) ve LAN IP'leri çalışır.
     */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('storage/'.ltrim($path, '/'));
    }
}
