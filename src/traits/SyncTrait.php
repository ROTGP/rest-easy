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

trait SyncTrait
{  
    protected function applySyncs($queriedModel, $authUser)
    {
        $safeSyncRelationships = $this->callProtectedMethod(
            $this->queriedModel(),
            'safeSyncRelationships',
            $authUser
        ) ?? [];

        if (empty($safeSyncRelationships))
            return;
        
        $queryParams = $this->queryParams();
        $verbs = ['attach', 'detach', 'sync'];
        foreach ($verbs as $verb) {
            foreach ($safeSyncRelationships as $relationship) {
                $key = $verb . '_' . $relationship;
                if (!array_key_exists($key, $queryParams))
                    continue;
                $ids = explode(',', $queryParams[$key]);
                if ($ids[0] === '')
                    $ids = [];
                if (sizeof($ids) > 0 && !array_product(array_map('ctype_digit', $ids)))
                    continue;

                if ($verb !== 'sync' && sizeof($ids) === 0)
                    continue;
                           
                $belongsToMany = $queriedModel->{$relationship}();
                $relatedPivotKey = $belongsToMany->getRelatedPivotKeyName();
                $sql = $belongsToMany->getQuery()->select($relatedPivotKey)->toSql();
                $sql = str_replace('?', $queriedModel->id, $sql);

                $existingIds = collect(DB::select(DB::raw($sql)))->pluck($relatedPivotKey)->unique();
                $toSync = collect($ids)->unique();
                
                $attaching = [];
                $detaching = [];
                $modelsToAttach = collect([]);
                $modelsToDetach = collect([]);

                if ($verb === 'attach') {
                    $attaching = $toSync->diff($existingIds)->toArray();
                } else if ($verb === 'detach') {
                    $detaching = $existingIds->intersect($toSync)->toArray();
                } else if ($verb === 'sync') {
                    $attaching = $toSync->diff($existingIds)->toArray();
                    $detaching = $existingIds->diff($toSync)->toArray();
                }

                if (sizeof($attaching) > 0)
                    $modelsToAttach = $belongsToMany->getRelated()->find($attaching)->unique();

                if (sizeof($detaching) > 0)
                    $modelsToDetach = $belongsToMany->getRelated()->find($detaching)->unique();

                foreach ($modelsToAttach as $modelToAttach) {
                    event('eloquent.attaching: ' . get_class($modelToAttach), [$queriedModel, $modelToAttach]);
                    event('eloquent.attaching: ' . get_class($modelToAttach), [$modelToAttach, $queriedModel]);
                }

                foreach ($modelsToDetach as $modelToDetach) {
                    event('eloquent.detaching: ' . get_class($modelToDetach), [$queriedModel, $modelToDetach]);
                    event('eloquent.detaching: ' . get_class($modelToDetach), [$modelToDetach, $queriedModel]);
                }
                if ($verb === 'attach') $verb = 'syncWithoutDetaching';
                $queriedModel->{$relationship}()->{$verb}($ids);

                foreach ($modelsToAttach as $modelToAttach) {
                    event('eloquent.attached: ' . get_class($modelToAttach), [$queriedModel, $modelToAttach]);
                    event('eloquent.attached: ' . get_class($modelToAttach), [$modelToAttach, $queriedModel]);
                }
                foreach ($modelsToDetach as $modelToDetach) {
                    event('eloquent.detached: ' . get_class($modelToDetach), [$queriedModel, $modelToDetach]);
                    event('eloquent.detached: ' . get_class($modelToDetach), [$modelToDetach, $queriedModel]);
                }
            }
        }
    }
}
