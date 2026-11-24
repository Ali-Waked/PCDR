<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OperationalRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Whitelisted search, filter, sort, and serialization for operational data.
 */
final class OperationalQueryService
{
    public function __construct(private readonly OperationCatalog $catalog) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return LengthAwarePaginator<int, OperationalRecord>
     */
    public function paginate(string $resource, array $parameters = []): LengthAwarePaginator
    {
        $query = $this->catalog->record($resource)->newQuery();
        if ($this->catalog->isTranslatable($resource)) {
            $query->with('translations');
        }

        $search = trim((string) ($parameters['q'] ?? ''));
        if ($search !== '') {
            $fields = $this->catalog->searchFields($resource);
            $query->where(function (Builder $builder) use ($fields, $search): void {
                foreach ($fields as $field) {
                    $builder->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }

        foreach ($this->catalog->filterFields($resource) as $field) {
            if (! array_key_exists($field, $parameters) || $parameters[$field] === '' || $parameters[$field] === null) {
                continue;
            }

            $query->where($field, $parameters[$field]);
        }

        $sort = (string) ($parameters['sort'] ?? 'id');
        if (! in_array($sort, $this->catalog->sortFields($resource), true)) {
            $sort = 'id';
        }

        $direction = strtolower((string) ($parameters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderBy('id', $direction);

        $perPage = (int) ($parameters['per_page'] ?? 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        return $query->paginate($perPage)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function serialize(OperationalRecord $record): array
    {
        $resource = $record->getResource();
        $data = [
            'id' => $record->getKey(),
            'resource' => $resource,
        ];

        foreach ($this->catalog->fields($resource) as $field) {
            $value = $record->getAttribute($field);
            $data[$field] = $value instanceof \Stringable ? (string) $value : $value;
        }

        $data['created_at'] = $record->created_at?->toISOString();
        $data['updated_at'] = $record->updated_at?->toISOString();

        if ($this->catalog->isTranslatable($resource)) {
            $translation = $record->translation();
            $data['translation'] = $translation === null
                ? null
                : $translation->only(array_merge(['locale'], $this->catalog->translationFields($resource)));
        }

        return $data;
    }

    /**
     * @param  LengthAwarePaginator<int, OperationalRecord>  $paginator
     * @return array<string, mixed>
     */
    public function paginationPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
