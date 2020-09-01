<?php

namespace ROTGP\RestEasy\Test\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AlbumUser extends Pivot
{
    public function canCreate($authUser)
    {
        return true;
    }
}