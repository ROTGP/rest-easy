<?php

namespace ROTGP\RestEasy\Test\Models;

class Song extends BaseModel
{
    protected $fillable = [
        'name', 'album_id', 'length_seconds' 
    ];

    protected $immutable = [
        'name',
        'album_id'
    ];

    protected $validationRules = [
        'name' => 'required',
        'album_id' => 'required|integer|exists',
        'length_seconds' => 'required|integer'
    ];

    public function safeRelationships($authUser)
    {
        return ['plays', 'album'];
    }

    public function plays()
    {
        return $this->hasMany(Play::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function canRead($authUser)
    {
        return true;
    }
}
