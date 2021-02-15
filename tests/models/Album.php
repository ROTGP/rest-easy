<?php

namespace ROTGP\RestEasy\Test\Models;

use Illuminate\Database\Eloquent\Model;
use ROTGP\RestEasy\Test\ErrorCodes;

class Album extends Model
{
    protected $fillable = [
        'name',
        'artist_id',
        'genre_id',
        'release_date',
        'price',
        'purchases' 
    ];

    public function validationRules($authUser)
    {
        return [
            'name' => 'required|unique|does_not_contain_genre_name',
            'artist_id' => 'required|integer|exists',
            'genre_id' => 'required|integer|exists',
            'release_date' => 'required|date',
            'price' => 'sometimes|numeric',
            'purchases' => 'sometimes|integer',
            'model' => 'album_count:5|genre_limit:4'
        ];
    }

    public function immutableFields($authUser)
    {
        return [
            'artist_id',
            'release_date',
            'price'
        ];
    }

    public function safeRelationships($authUser)
    {
        return ['artist', 'genre', 'users', 'artist.record_label'];
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

    public function validateDoesNotContainGenreName($field, $value, $params)
    {
        if (strpos($value, $this->genre->name) !== false)
            return 'The album name must not contain the name of the genre.';
    }

    public function validateAlbumCount($field, $value, $params)
    {
        if (request()->method() === 'PUT')
            return;
        $limit = $params[0];
        if ($this->where('artist_id', $value['artist_id'])->count() >= $limit)
            return 'An artist may only have up to ' . $limit . ' albums.';
    }
    
    public function validateGenreLimit($field, $value, $params)
    {
        $limit = $params[0];
        $artistId = $value['artist_id'];
        $genreId = $value['genre_id'];
        $existingGenres = $this->select('genre_id')
            ->where('artist_id', $value['artist_id'])
            ->groupBy('genre_id')
            ->pluck('genre_id');
        if (!$existingGenres->contains($genreId))
            $existingGenres->push($genreId);
        if (sizeof($existingGenres) <= $limit) 
            return;
        return 'An artist may not have albums belonging to more than ' . $limit . ' genres.';
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function canRead($authUser)
    {
        return in_array($authUser->id, [1, 2, 9]) ? true : ErrorCodes::USER_NOT_AUTHORIZED_TO_ACCESS_ALBUM;
    }

    public function canCreate($authUser)
    {
        return in_array($authUser->id, [1, 2]);
    }

    public function canUpdate($authUser)
    {
        return in_array($authUser->id, [1, 2]);
    }

    public function canDelete($authUser)
    {
        return in_array($authUser->id, [1, 2]);
    }

    public function canAttach($modelToAttach, $authUser)
    {
        return in_array($authUser->id, [1, 2]);
    }

    public function canDetach($modelToDetach, $authUser)
    {
        return in_array($authUser->id, [1, 2]);
    }
}
