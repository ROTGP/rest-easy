<?php

namespace ROTGP\RestEasy\Test\Models;

use Illuminate\Database\Eloquent\Model;
use ROTGP\RestEasy\Test\ErrorCodes;

class RecordLabel extends Model
{
    const WARNER_BROS = 1; 
    const ISLAND_DEF_JAM = 2;
    const AFTERMATH = 3;
    const EPIC = 4;
    const ATLANTIC = 5;
    const YOUNG_MONEY_ENTERTAINMENT = 6;
    const CASH_MONEY_BILLIONAIRE_RECORDS = 7;
    const COLUMBIA = 8;

    public $timestamps = false;

    public function artists()
    {
        return $this->hasMany(Artist::class);
    }

    public function canRead($authUser)
    {
        if ($authUser->id === 9)
            return ErrorCodes::USER_NOT_AUTHORIZED_TO_ACCESS_RECORD_LABEL;
        return true;
    }
}
