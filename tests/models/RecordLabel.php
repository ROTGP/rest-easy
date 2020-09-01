<?php

namespace ROTGP\RestEasy\Test\Models;

class RecordLabel extends BaseModel
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

    public function canRead($authUser)
    {
        return true;
    }
}
