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
        $safeSyncRelationships = $this->callModelMethod(
            $this->queriedModel(),
            'safeSyncRelationships',
            $authUser
        ) ?? [];

        if (empty($safeSyncRelationships))
            return;
        
        $queryParams = $this->queryParams();
        if (empty($queryParams))
            return;
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
                $sql = str_replace('?', $queriedModel->getKey(), $sql);

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

                if (sizeof($attaching) > 0) {
                    $this->disableListeningForModelEvents();
                    $modelsToAttach = $belongsToMany->getRelated()->find($attaching)->unique();
                    $this->enableListeningForModelEvents();
                }
                    

                if (sizeof($detaching) > 0) {
                    $this->disableListeningForModelEvents();
                    $modelsToDetach = $belongsToMany->getRelated()->find($detaching)->unique();
                    $this->enableListeningForModelEvents();
                }
                    
                foreach ($modelsToAttach as $modelToAttach) {
                    $this->onModelEvent('eloquent.attaching: ' . get_class($modelToAttach), [$queriedModel, $modelToAttach], true);
                    $this->onModelEvent('eloquent.attaching: ' . get_class($modelToAttach), [$modelToAttach, $queriedModel], true);
                }

                foreach ($modelsToDetach as $modelToDetach) {
                    $this->onModelEvent('eloquent.detaching: ' . get_class($modelToDetach), [$queriedModel, $modelToDetach], true);
                    $this->onModelEvent('eloquent.detaching: ' . get_class($modelToDetach), [$modelToDetach, $queriedModel], true);
                }
                if ($verb === 'attach') $verb = 'syncWithoutDetaching';
                $queriedModel->{$relationship}()->{$verb}($ids);

                foreach ($modelsToAttach as $modelToAttach) {
                    $this->onModelEvent('eloquent.attached: ' . get_class($modelToAttach), [$queriedModel, $modelToAttach], true);
                    $this->onModelEvent('eloquent.attached: ' . get_class($modelToAttach), [$modelToAttach, $queriedModel], true);
                }
                foreach ($modelsToDetach as $modelToDetach) {
                    $this->onModelEvent('eloquent.detached: ' . get_class($modelToDetach), [$queriedModel, $modelToDetach], true);
                    $this->onModelEvent('eloquent.detached: ' . get_class($modelToDetach), [$modelToDetach, $queriedModel], true);
                }
            }
        }
    }
}
