<?php

declare(strict_types=1);

namespace App\Graphql\Queries;

use App\Models\User;
use Fam\Sources\Models\Source;
use Illuminate\Database\Eloquent\Collection;

final class SourcesQueries
{
    /**
     * List every source owned by the authenticated user.
     *
     * @param  array{}  $args
     * @return Collection<int, Source>
     */
    public function sources(mixed $root, array $args): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Source::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->get();
    }
}
