<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

use Str;
use DB;

trait QueryTrait
{  
    protected $aggregateFunctions = ['count', 'sum', 'avg', 'min', 'max'];
    protected $appliedAggregateFunctions = [];
    protected $appliedGroupBy = [];
    protected $appliedWithCount = [];

    protected function applyQueryFilters($model = null) : Collection {
        $this->applySelects();
        $this->applyWiths();
        
        if ($model !== null) {
            $this->ignoreModel = [$model::class, $model->getKey()];
            $collection = $this->query->get();
            $this->ignoreModel = [];
            return $collection;
        }
        $this->applyScopes();
        $this->applyGroupBy();
        $this->applyAggregations();
        $this->applyOrderBy();
        return $this->applyPagination($this->query);
    }

    protected function getSafeScopes() : array
    {
        $safeScopes = $this->callModelMethod(
            $this->queriedModel(),
            'safeScopes',
            $this->authUser()
        ) ?? [];
        if (empty($safeScopes))
            return [];
        
        $scopes = $this->toStudlyArray($safeScopes);
        if (empty($scopes))
            return [];
        for($i = 0; $i < sizeof($scopes); $i++)
            $scopes[$i] = 'scope' . $scopes[$i];
        return $scopes;
    }

    protected function getSafeRelationships() : array
    {
        return $this->callModelMethod(
            $this->queriedModel(),
            'safeRelationships',
            $this->authUser()
        ) ?? [];
    }
    
    public function applyScopes() : void
    {
        if (method_exists($this->model, 'scopeImplicit'))
            $this->query->{'implicit'}(
                $this->authUser,
                $this->payload(),
                $this->queryParams()
            );
        $scopes = $this->getSafeScopes();
        if (empty($scopes))
            return;
        $queryParams = $this->toSnakeArray($this->queryParams());
        if (empty($queryParams))
            return;
        for ($i = 0; $i < sizeof($scopes); $i++) {
            $scopeKey = Str::snake(substr($scopes[$i], 5));
            if (!array_key_exists($scopeKey, $queryParams))
                continue;
            $params = $queryParams[$scopeKey];
            if ($params !== null) {
                // comma-separated array of ints
                if (preg_match('/^\d+(?:,\d+)*$/', $params) && !ctype_digit($params)) {
                    $params = array_map('intval', explode(',', $params));
                // single int
                } else if (ctype_digit($params)) {
                    $params = intval($params);
                // single double
                } else if (is_numeric($params)) {
                    $params = doubleval($params);
                }
                
                // else, strings and comma-separated strings should be parsed
                // manually by the scope accepting the param. The reason for 
                // this is that a single string may contain a comma, and there
                // is no obvious way of differentiating strings and arrays 
                // of strings.
            }
            $scopeKey = Str::camel($scopeKey);
            if ($params !== null) {
                $this->query->{$scopeKey}($params);
            } else {
                $this->query->{$scopeKey}();
            }
        }
    }


    public function applyOrderBy() : void
    {
        $queryParams = $this->queryParams();
        if (!array_key_exists('order_by', $queryParams))
            return;
        $orderBy = $queryParams['order_by'];
        if (empty($orderBy) || !is_string($orderBy))
            return;
        $safeColumns = $this->getColumns();
        if (sizeof($this->appliedGroupBy) > 0) {
            $safeColumns = array_merge(
                $this->appliedGroupBy,
                $this->appliedAggregateFunctions
            );
        }
        $orderBy = explode(',', $orderBy);
        $direction = in_array('desc', $orderBy) ? 'desc' : 'asc';
        $orderBy = array_values(
            array_intersect(
                $orderBy,
                $safeColumns
            )
        );
        if (sizeof($orderBy) === 0)
            return;
        foreach ($orderBy as $ob)
            $this->query->orderBy($ob, $direction);
    }

    public function applyGroupBy() : void
    {
        $queryParams = $this->queryParams();
        if (!array_key_exists('group_by', $queryParams))
            return;
        $groupBy = $queryParams['group_by'];
        if (empty($groupBy) || !is_string($groupBy))
            return;
        $groupBy = array_values(
            array_intersect(
                explode(',', $groupBy),
                $this->getColumns()
            )
        );
        if (sizeof($groupBy) === 0)
            return;

        $this->appliedGroupBy = $groupBy;
        $this->query->groupBy($groupBy);
        $this->query->select($groupBy);
    }

    protected function applyAggregations() : void
    {
        $queryParams = $this->queryParams();
        if (empty($queryParams))
            return;
        $columns = $this->getColumns();
        foreach ($this->aggregateFunctions as $aggFunc) {
            if (!array_key_exists($aggFunc, $queryParams))
                continue;
            $items = explode(',', $queryParams[$aggFunc]);
            foreach ($items as $item) {
                if (!in_array($item, $columns)) continue;
                $func = $item . '_' . $aggFunc;
                $this->appliedAggregateFunctions[] = $func;
                $sql = "{$aggFunc}($item) as {$func}";
                // $roundSql = "round({$aggFunc}($item), 2) as {$item}_{$aggFunc}";
                $this->query->addSelect(DB::raw($sql));
            }
        }
    }

    public function applyPagination(Builder $query) : Collection
    {
        $queryParams = $this->queryParams();
        $page = $queryParams['page'] ?? null;
        $pageSize = $queryParams['page_size'] ?? null;
        if (!ctype_digit($page) || !ctype_digit($pageSize))
            return $query->get();
        $page = intVal($page);
        $pageSize = intVal($pageSize);
        
        $countQuery = "select count(*) as total_results from ({$query->toSql()}) c";
        $totalResults = collect(DB::select($countQuery, $query->getBindings()))->pluck('total_results')->first();
        $totalPages = ceil($totalResults / $pageSize);

        if ($page <= 0) {
            $page = 1;
        } else if ($page > $totalPages) {
            $page = $totalPages;
        }

        $query->skip(($page - 1) * ($pageSize));
        $query->take($pageSize);

        return new Collection([
            'page' => $page,
            'page_size' => $pageSize,
            'total_results' => $totalResults,
            'total_pages' => $totalPages,
            'data' => $query->get()
        ]);
    }

    public function applySelects() : void
    {
        $queryParams = $this->queryParams();
        if (!array_key_exists('select', $queryParams))
            return;
        $selects = $queryParams['select'];
        if (empty($selects) || !is_string($selects))
            return;
        $selects = array_values(
            array_intersect(
                explode(',', $selects),
                $this->getColumns()
            )
        );
        if (sizeof($selects) === 0)
            return;
        $this->query->select($selects);
    }

    public function applyWiths() : void
    {
        $safeRelationships = $this->getSafeRelationships();
        if (empty($safeRelationships))
            return;
        $this->applyWith($safeRelationships);
        $this->applyWithCount($safeRelationships);
    }

    public function applyWith($safeRelationships) : void
    {
        $queryParams = $this->queryParams();
        if (!array_key_exists('with', $queryParams))
            return;
        $withs = $queryParams['with'];
        if (empty($withs) || !is_string($withs))
            return;
        $withs = $this->toCamelArray(
            array_values(
                array_intersect(
                    explode(',', $withs),
                    $safeRelationships
                )
            )
        );
        if (sizeof($withs) === 0)
            return;
        $this->query->with($withs);
    }

    public function applyWithCount($safeRelationships) : void
    {
        $queryParams = $this->queryParams();
        if (!array_key_exists('with_count', $queryParams))
            return;
        $withs = $queryParams['with_count'];
        if (empty($withs) || !is_string($withs))
            return;
        $withs = $this->toCamelArray(
            array_values(
                array_intersect(
                    explode(',', $withs),
                    $safeRelationships
                )
            )
        );
        if (sizeof($withs) === 0)
            return;
        foreach ($withs as $with)
            $this->appliedWithCount[] = $with . '_count';
        $this->query->withCount($withs);
    }
}
