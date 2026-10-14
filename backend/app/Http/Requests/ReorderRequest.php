<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['present', 'array'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        return array_map(intval(...), $this->array('ids'));
    }
}
