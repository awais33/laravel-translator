<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class ApiRequest extends FormRequest
{
    protected int $defaultPerPage    = 20;
    protected int $maxPerPage        = 100;
    protected string $defaultSortBy  = 'id';
    protected string $defaultSortDir = 'desc';

    /** Subclasses declare which columns are safe to sort on */
    protected array $allowedSortFields = ['id', 'created_at', 'updated_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1',
            'sort_by'        => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
            'paginate'       => 'nullable|boolean',
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input('page', 1);
    }

    public function getPerPage(): int
    {
        $requested = (int) $this->input('per_page', $this->defaultPerPage);

        return min(max($requested, 1), $this->maxPerPage);
    }

    public function getSortBy(): string
    {
        $requested = $this->input('sort_by', $this->defaultSortBy);

        return in_array($requested, $this->allowedSortFields, true)
            ? $requested
            : $this->defaultSortBy;
    }

    public function getSortDirection(): string
    {
        $dir = strtolower((string) $this->input('sort_direction', $this->defaultSortDir));

        return $dir === 'asc' ? 'asc' : 'desc';
    }

    public function shouldPaginate(): bool
    {
        return filter_var($this->input('paginate', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Apply ordering from this request onto any query builder.
     * Subclasses can override to add additional default scopes.
     */
    public function applyOrdering(Builder $query): Builder
    {
        return $query->orderBy($this->getSortBy(), $this->getSortDirection());
    }

    /**
     * Return only the filter keys defined by each subclass.
     * Subclasses should override this to return their specific filter fields.
     */
    public function filters(): array
    {
        return [];
    }
}
