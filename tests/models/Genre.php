<?php

namespace ROTGP\RestEasy\Test\Models;

class Genre extends BaseModel
{
    const ROCK = 1; 
    const FOLK = 2;
    const HEAVY_METAL = 3;
    const PUNK = 4;
    const HIP_HOP = 5;
    const POP = 6;
    const FUNK = 7;
    const JAZZ = 8;
    const HOUSE = 9;
    const BLUES = 10;
    const CLASSICAL = 11;
    const TECHNO = 12;

    public $timestamps = false;

    public function canRead($authUser)
    {
        return true;
    }
}
