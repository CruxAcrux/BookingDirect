<?php

namespace App\Actions\Schedule;

use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ReplaceWeeklyHours
{
    /**
     * Swaps the whole week in one transaction, so a schedule is never half-saved.
     *
     * @template TModel of Model
     *
     * @param  HasMany<TModel, covariant Model>  $relation
     * @param  iterable<array{weekday: Weekday, start: string, end: string}>  $ranges
     * @return Collection<int, TModel>
     */
    public function handle(HasMany $relation, iterable $ranges, string $startColumn, string $endColumn): Collection
    {
        return DB::transaction(function () use ($relation, $ranges, $startColumn, $endColumn): Collection {
            $relation->delete();

            foreach ($ranges as $range) {
                $relation->create([
                    'weekday' => $range['weekday'],
                    $startColumn => $range['start'],
                    $endColumn => $range['end'],
                ]);
            }

            return $relation->orderBy('weekday')->orderBy($startColumn)->get();
        });
    }
}
