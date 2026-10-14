<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderModels
{
    /**
     * Sets `sort_order` from the position of each id in the list. The list must contain
     * exactly the records in the query, so nothing is reordered from another scope.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, int>  $ids
     */
    public function handle(Builder $query, array $ids): void
    {
        $existing = (clone $query)->pluck($query->getModel()->getKeyName())->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $requested = collect($ids)->map(fn ($id): int => (int) $id)->sort()->values()->all();

        if ($existing !== $requested) {
            throw ValidationException::withMessages(['ids' => __('rules.order_mismatch')]);
        }

        DB::transaction(function () use ($query, $ids): void {
            foreach (array_values($ids) as $position => $id) {
                (clone $query)->whereKey($id)->update(['sort_order' => $position]);
            }
        });
    }
}
