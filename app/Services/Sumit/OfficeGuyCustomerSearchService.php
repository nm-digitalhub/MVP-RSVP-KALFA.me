<?php

declare(strict_types=1);

namespace App\Services\Sumit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OfficeGuy\LaravelSumitGateway\Contracts\HasSumitCustomer;
use OfficeGuy\LaravelSumitGateway\Models\CrmEntity;
use OfficeGuy\LaravelSumitGateway\Models\CrmFolder;
use OfficeGuy\LaravelSumitGateway\Services\CrmDataService;
use OfficeGuy\LaravelSumitGateway\Services\CrmSchemaService;
use OfficeGuy\LaravelSumitGateway\Services\CustomerMergeService;

final class OfficeGuyCustomerSearchService
{
    private const int RemotePageSize = 100;

    private const int RemotePageLimit = 10;

    public function __construct(
        private readonly CustomerMergeService $customerMergeService,
    ) {}

    /**
     * @return list<array{
     *     source:string,
     *     source_label:string,
     *     model_class:?string,
     *     model_id:?int,
     *     name:string,
     *     email:?string,
     *     phone:?string,
     *     sumit_customer_id:int
     * }>
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $results = [];
        $modelClass = $this->customerModelClass();

        if ($modelClass !== null) {
            foreach ($this->searchLocalCandidates($modelClass, $term) as $candidate) {
                $mappedCandidate = $this->mapLocalCandidate($candidate);

                if ($mappedCandidate === null) {
                    continue;
                }

                $results[(string) $mappedCandidate['sumit_customer_id']] = $mappedCandidate;
            }
        }

        foreach ($this->searchLocalCrmCandidates($term) as $candidate) {
            $mappedCandidate = $this->mapCrmCandidate($candidate, 'sumit_crm_cache', 'SUMIT CRM');

            if ($mappedCandidate !== null) {
                $results[(string) $mappedCandidate['sumit_customer_id']] = $mappedCandidate;
            }
        }

        if ($results === []) {
            foreach ($this->searchRemoteCrmCandidates($term) as $remoteCandidate) {
                $results[(string) $remoteCandidate['sumit_customer_id']] = $remoteCandidate;

                if (count($results) >= 8) {
                    break;
                }
            }
        }

        return array_values(array_slice($results, 0, 8));
    }

    public function customerModelLabel(): ?string
    {
        $modelClass = $this->customerModelClass();

        if ($modelClass === null) {
            return null;
        }

        return class_basename($modelClass);
    }

    private function customerModelClass(): ?string
    {
        $modelClass = $this->customerMergeService->getModelClass();

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return null;
        }

        return $modelClass;
    }

    /**
     * @return list<Model>
     */
    private function searchLocalCandidates(string $modelClass, string $term): array
    {
        /** @var Model $model */
        $model = new $modelClass;
        $columns = $this->resolveColumns($model);

        $query = $modelClass::query();

        if (method_exists($model, 'account')) {
            $query->with('account');
        }

        $query->where(function (Builder $builder) use ($term, $columns, $model): void {
            $firstConstraint = true;

            if (ctype_digit($term)) {
                $sumitCustomerId = (int) $term;

                if ($columns['sumit_id'] !== null) {
                    $builder->where($columns['sumit_id'], $sumitCustomerId);
                    $firstConstraint = false;
                }

                if (method_exists($model, 'account')) {
                    if ($firstConstraint) {
                        $builder->whereHas('account', function (Builder $accountQuery) use ($sumitCustomerId): void {
                            $accountQuery->where('sumit_customer_id', $sumitCustomerId);
                        });
                    } else {
                        $builder->orWhereHas('account', function (Builder $accountQuery) use ($sumitCustomerId): void {
                            $accountQuery->where('sumit_customer_id', $sumitCustomerId);
                        });
                    }

                    $firstConstraint = false;
                }
            }

            if (filter_var($term, FILTER_VALIDATE_EMAIL) !== false) {
                foreach (array_filter([$columns['email']]) as $column) {
                    if ($firstConstraint) {
                        $builder->where($column, $term);
                        $firstConstraint = false;
                    } else {
                        $builder->orWhere($column, $term);
                    }
                }

                if (method_exists($model, 'account')) {
                    if ($firstConstraint) {
                        $builder->whereHas('account.owner', function (Builder $ownerQuery) use ($term): void {
                            $ownerQuery->where('email', $term);
                        });
                    } else {
                        $builder->orWhereHas('account.owner', function (Builder $ownerQuery) use ($term): void {
                            $ownerQuery->where('email', $term);
                        });
                    }

                    $firstConstraint = false;
                }
            }

            foreach (array_filter([$columns['name'], $columns['email'], $columns['phone']]) as $column) {
                if ($firstConstraint) {
                    $builder->where($column, $this->likeOperator(), '%'.$term.'%');
                    $firstConstraint = false;
                } else {
                    $builder->orWhere($column, $this->likeOperator(), '%'.$term.'%');
                }
            }

            if (method_exists($model, 'account')) {
                if ($firstConstraint) {
                    $builder->whereHas('account', function (Builder $accountQuery) use ($term): void {
                        $accountQuery
                            ->where('name', $this->likeOperator(), '%'.$term.'%')
                            ->orWhereHas('owner', function (Builder $ownerQuery) use ($term): void {
                                $ownerQuery->where('email', $this->likeOperator(), '%'.$term.'%');
                            });
                    });
                } else {
                    $builder->orWhereHas('account', function (Builder $accountQuery) use ($term): void {
                        $accountQuery
                            ->where('name', $this->likeOperator(), '%'.$term.'%')
                            ->orWhereHas('owner', function (Builder $ownerQuery) use ($term): void {
                                $ownerQuery->where('email', $this->likeOperator(), '%'.$term.'%');
                            });
                    });
                }
            }
        });

        return $query
            ->limit(8)
            ->get()
            ->all();
    }

    /**
     * @return list<CrmEntity>
     */
    private function searchLocalCrmCandidates(string $term): array
    {
        $query = CrmEntity::withoutGlobalScopes()->newQuery();

        $query->where(function (Builder $builder) use ($term): void {
            $firstConstraint = true;

            if (ctype_digit($term)) {
                $builder->where('sumit_entity_id', (int) $term);
                $firstConstraint = false;
            }

            if (filter_var($term, FILTER_VALIDATE_EMAIL) !== false) {
                if ($firstConstraint) {
                    $builder->where('email', $term);
                } else {
                    $builder->orWhere('email', $term);
                }

                $firstConstraint = false;
            }

            foreach (['name', 'email', 'phone', 'mobile', 'company_name'] as $column) {
                if ($firstConstraint) {
                    $builder->where($column, $this->likeOperator(), '%'.$term.'%');
                    $firstConstraint = false;
                } else {
                    $builder->orWhere($column, $this->likeOperator(), '%'.$term.'%');
                }
            }
        });

        return $query
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->all();
    }

    /**
     * @return array{name:?string,email:?string,phone:?string,sumit_id:?string}
     */
    private function resolveColumns(Model $model): array
    {
        $fieldMap = $this->customerMergeService->getFieldMapping();
        $table = $model->getTable();

        return [
            'name' => $this->firstExistingColumn($table, [$fieldMap['name'] ?? null, 'name', 'full_name']),
            'email' => $this->firstExistingColumn($table, [$fieldMap['email'] ?? null, 'billing_email', 'email']),
            'phone' => $this->firstExistingColumn($table, [$fieldMap['phone'] ?? null, 'phone', 'mobile', 'telephone']),
            'sumit_id' => $this->firstExistingColumn($table, [$fieldMap['sumit_id'] ?? null, 'sumit_customer_id']),
        ];
    }

    /**
     * @param  list<?string>  $candidates
     */
    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     source:string,
     *     source_label:string,
     *     model_class:?string,
     *     model_id:?int,
     *     name:string,
     *     email:?string,
     *     phone:?string,
     *     sumit_customer_id:int
     * }|null
     */
    private function mapLocalCandidate(Model $candidate): ?array
    {
        if (! $candidate instanceof HasSumitCustomer) {
            return null;
        }

        $sumitCustomerId = $candidate->getSumitCustomerId();

        if ($sumitCustomerId === null) {
            return null;
        }

        return [
            'source' => 'officeguy_customer_model',
            'source_label' => class_basename($candidate::class),
            'model_class' => $candidate::class,
            'model_id' => (int) $candidate->getKey(),
            'name' => $candidate->getSumitCustomerName() ?: 'SUMIT Customer',
            'email' => $candidate->getSumitCustomerEmail(),
            'phone' => $candidate->getSumitCustomerPhone(),
            'sumit_customer_id' => (int) $sumitCustomerId,
        ];
    }

    /**
     * @return array{
     *     source:string,
     *     source_label:string,
     *     model_class:?string,
     *     model_id:?int,
     *     name:string,
     *     email:?string,
     *     phone:?string,
     *     sumit_customer_id:int
     * }|null
     */
    private function mapCrmCandidate(CrmEntity $candidate, string $source, string $sourceLabel): ?array
    {
        $sumitCustomerId = $candidate->sumit_customer_id ?? $candidate->sumit_entity_id;

        if ($sumitCustomerId === null) {
            return null;
        }

        return [
            'source' => $source,
            'source_label' => $sourceLabel,
            'model_class' => $candidate::class,
            'model_id' => (int) $candidate->getKey(),
            'name' => $candidate->name ?: 'SUMIT Customer',
            'email' => $candidate->email,
            'phone' => $candidate->phone ?: $candidate->mobile,
            'sumit_customer_id' => (int) $sumitCustomerId,
        ];
    }

    /**
     * @return list<array{
     *     source:string,
     *     source_label:string,
     *     model_class:?string,
     *     model_id:?int,
     *     name:string,
     *     email:?string,
     *     phone:?string,
     *     sumit_customer_id:int
     * }>
     */
    private function searchRemoteCrmCandidates(string $term): array
    {
        $folderIds = $this->customerFolderIds();

        if ($folderIds === []) {
            CrmSchemaService::syncAllFolders();
            $folderIds = $this->customerFolderIds();
        }

        if ($folderIds === []) {
            return [];
        }

        $results = [];

        foreach ($folderIds as $folderId) {
            for ($page = 0; $page < self::RemotePageLimit; $page++) {
                $listResponse = CrmDataService::listEntities($folderId, [
                    'Paging' => [
                        'StartIndex' => $page * self::RemotePageSize,
                        'PageSize' => self::RemotePageSize,
                    ],
                ]);

                if (($listResponse['success'] ?? false) !== true) {
                    break;
                }

                $entities = $listResponse['entities'] ?? [];

                if (! is_array($entities) || $entities === []) {
                    break;
                }

                foreach ($entities as $entitySummary) {
                    $sumitEntityId = $entitySummary['ID'] ?? $entitySummary['EntityID'] ?? null;

                    if (! is_numeric($sumitEntityId)) {
                        continue;
                    }

                    $candidate = $this->fetchRemoteCrmCandidate((int) $sumitEntityId, $term);

                    if ($candidate === null) {
                        continue;
                    }

                    $results[(string) $candidate['sumit_customer_id']] = $candidate;

                    if (count($results) >= 8) {
                        return array_values($results);
                    }
                }

                if (count($entities) < self::RemotePageSize) {
                    break;
                }
            }
        }

        return array_values($results);
    }

    /**
     * @return array{
     *     source:string,
     *     source_label:string,
     *     model_class:?string,
     *     model_id:?int,
     *     name:string,
     *     email:?string,
     *     phone:?string,
     *     sumit_customer_id:int
     * }|null
     */
    private function fetchRemoteCrmCandidate(int $sumitEntityId, string $term): ?array
    {
        $entityResponse = CrmDataService::getEntity($sumitEntityId);

        if (($entityResponse['success'] ?? false) !== true) {
            return null;
        }

        $entity = $entityResponse['entity']['Entity'] ?? $entityResponse['entity'] ?? null;

        if (! is_array($entity)) {
            return null;
        }

        $candidate = [
            'source' => 'sumit_crm_remote',
            'source_label' => 'SUMIT CRM',
            'model_class' => null,
            'model_id' => null,
            'name' => $this->resolveRemoteEntityName($entity, $sumitEntityId),
            'email' => $entity['Customers_EmailAddress'][0] ?? $entity['Email'] ?? null,
            'phone' => $entity['Customers_Phone'][0] ?? $entity['Phone'] ?? null,
            'sumit_customer_id' => (int) ($entity['CustomerID'] ?? $entity['ID'] ?? $sumitEntityId),
        ];

        if (! $this->candidateMatchesTerm($candidate, $term)) {
            return null;
        }

        return $candidate;
    }

    /**
     * @return list<int>
     */
    private function customerFolderIds(): array
    {
        return CrmFolder::query()
            ->whereIn('name', ['לקוחות', 'לקוחות/ספקים'])
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array{
     *     source:string,
     *     source_label:string,
     *     model_class:?string,
     *     model_id:?int,
     *     name:string,
     *     email:?string,
     *     phone:?string,
     *     sumit_customer_id:int
     * }  $candidate
     */
    private function candidateMatchesTerm(array $candidate, string $term): bool
    {
        if (ctype_digit($term)) {
            return (string) $candidate['sumit_customer_id'] === $term;
        }

        if (filter_var($term, FILTER_VALIDATE_EMAIL) !== false) {
            return strcasecmp((string) ($candidate['email'] ?? ''), $term) === 0;
        }

        $needle = mb_strtolower($term);

        foreach ([$candidate['name'], $candidate['email'], $candidate['phone']] as $value) {
            if ($value !== null && mb_stripos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    private function resolveRemoteEntityName(array $entity, int $fallbackId): string
    {
        $candidates = [
            $entity['Customers_FullName'][0] ?? null,
            $entity['Billing_Name'][0] ?? null,
            $entity['Books_Name'][0] ?? null,
            $entity['Name'] ?? null,
            $entity['EntityName'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return 'SUMIT Customer #'.$fallbackId;
    }

    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
