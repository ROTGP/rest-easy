<?php

namespace ROTGP\RestEasy\Test\Models;

class Song extends BaseModel
{
    protected $fillable = [
        'name',
        'album_id',
        'length_seconds' 
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
        return [
            'plays',
            'album',
            'users'
        ];
    }

    public function plays()
    {
        return $this->hasMany(Play::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeImplicit($query)
    {
        $authUserId = optional(auth()->user())->id;
        if ($authUserId === null) 
            return;
        $query->whereHas('users', function ($q) use ($authUserId) {
            // dd($authUserId);
            $q->where('song_user.user_id', $authUserId); 
            // dd($q->toSql()); 
        });
    }

    public function canRead($authUser)
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
