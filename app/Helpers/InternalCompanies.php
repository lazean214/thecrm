<?php

namespace App\Helpers;

use App\Models\InternalCompany;
use Illuminate\Support\Collection;

class InternalCompanies
{
    private static function allFromDb(): Collection
    {
        return InternalCompany::orderBy('name')->get();
    }

    public static function all(): array
    {
        return self::allFromDb()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
        ])->values()->toArray();
    }

    public static function get(int $id): ?array
    {
        $company = InternalCompany::find($id);

        if (! $company) {
            return null;
        }

        return ['id' => $company->id, 'name' => $company->name, 'slug' => $company->slug];
    }

    public static function getBySlug(string $slug): ?array
    {
        $company = InternalCompany::where('slug', $slug)->first();

        if (! $company) {
            return null;
        }

        return ['id' => $company->id, 'name' => $company->name, 'slug' => $company->slug];
    }

    public static function names(): array
    {
        return self::allFromDb()->pluck('name')->toArray();
    }
}
