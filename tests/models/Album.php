<?php

namespace ROTGP\RestEasy\Test\Models;

class Album extends BaseModel
{
    protected $fillable = [
        'name', 'artist_id', 'genre_id', 'release_date', 'price', 'purchases' 
    ];

    protected function validationRules($authUser)
    {
        return [
            'name' => 'required',
            'artist_id' => 'required|integer|exists',
            'genre_id' => 'required|integer|exists',
            'release_date' => 'required|date',
            'price' => 'sometimes|numeric',
            'purchases' => 'sometimes|integer'
        ];
    }

    public function immutableFields($authUser)
    {
        return ['name', 'artist_id', 'release_date', 'price'];
    }

    public function safeRelationships($authUser)
    {
        return ['artist', 'genre', 'users'];
    }

    public function safeSyncRelationships($authUser)
    {
        return ['users'];
    }

    public function safeScopes($authUser)
    {
        return [];
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function canRead($authUser)
    {
        return true;
    }

    public function canUpdate($authUser)
    {
        return true;
    }

    public function canAttach($modelToAttach, $authUser)
    {
        return true;
    }
    

    public function canDetach($modelToDetach, $authUser)
    {
        return true;
    }
    
}
