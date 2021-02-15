<?php

namespace ROTGP\RestEasy\Test\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email', 
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected function validationRules($authUser)
    {
        return [
            'username' => 'required|unique',
            'email' => 'required|unique|email',
            'password' => 'required|min:8',
            'date_of_birth' => 'required|date',
            'phone_number' => 'required'
        ];
    }

    public function immutableFields($authUser)
    {
        return [
            'username',
            'role_id',
            'date_of_birth'
        ];
    }

    public function safeRelationships($authUser)
    {
        return ['albums', 'songs'];
    }

    public function safeSyncRelationships($authUser)
    {
        return ['albums', 'songs'];
    }

    public function safeScopes($authUser)
    {
        return ['born_after'];
    }

    public function scopeBornAfter($query, $params)
    {
        return $query->where('date_of_birth', '>', Carbon::parse($params));
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class);
    }

    public function albums()
    {
        return $this->belongsToMany(Album::class)->using(AlbumUser::class)->withTimestamps();
    }

    public function songs()
    {
        return $this->belongsToMany(Song::class)->withTimestamps();;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function canRead($authUser)
    {
        if ($authUser->id === 9 && $this->id === 8) return false;
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

