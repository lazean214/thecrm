<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class InternalCompanies
{
    /**
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public static function all(): array
    {
        $companies = config('internal_companies.companies', []);
        $result = [];

        foreach ($companies as $index => $name) {
            if (empty(trim($name))) {
                continue;
            }

            $result[] = [
                'id' => $index + 1,
                'name' => trim($name),
                'slug' => Str::slug($name),
            ];
        }

        return $result;
    }

    public static function get(int $id): ?array
    {
        $all = static::all();

        return $all[$id - 1] ?? null;
    }

    public static function getBySlug(string $slug): ?array
    {
        return collect(static::all())->firstWhere('slug', $slug);
    }

    public static function names(): array
    {
        return collect(static::all())->pluck('name')->toArray();
    }
}
