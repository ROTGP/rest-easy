<?php

namespace ROTGP\RestEasy\Test\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function canRead($authUser)
    {
        return false;
    }

    public function canCreate($authUser)
    {
        return false;
    }

    public function canUpdate($authUser)
    {
        return false;
    }

    public function canDelete($authUser)
    {
        return false;
    }

    public function canAttach($modelToAttach, $authUser)
    {
        return false;
    }

    public function canDetach($modelToDetach, $authUser)
    {
        return false;
    }
}
