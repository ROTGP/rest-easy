<?php

namespace ROTGP\RestEasy\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $fillable = [
        'name',
        'biography',
        'record_label_id',
        'fan_mail_address'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'record_label_id' => 'int'
    ];

    protected function validationRules($authUser)
    {
        return [
            'name' => 'required|unique',
            'biography' => 'required|unique|max:500',
            'record_label_id' => 'required|integer|exists',
            'fan_mail_address' => 'nullable'
        ];
    }

    public function immutableFields($authUser)
    {
        return ['name'];
    }

    public function safeRelationships($authUser)
    {
        $userId = $authUser->id;
        if ($userId === null) {
            return [];
        } else if ($userId === 1) {
            return ['record_label', 'users', 'albums', 'genres'];
        } else {
            return ['record_label', 'albums'];
        }
    }

    public function safeScopes($authUser)
    {
        return [
            'record_labels',
            'name_like'
        ];
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function recordLabel()
    {
        return $this->belongsTo(RecordLabel::class);
    }

    public function scopeNameLike($query, $searchTerm)
    {
        return $query->where('name', 'like', "%" . $searchTerm . "%");
    }

    public function scopeRecordLabels($query, $recordLabelIds)
    {
        return $query->whereIn('record_label_id', (array) $recordLabelIds);
    }

    public function canRead($authUser)
    {
        return $authUser->id !== 3;
    }

    public function canUpdate($authUser)
    {
        return true;
    }

    public function canCreate($authUser)
    {
        return true;
    }

    public function canDelete($authUser)
    {
        return true;
    }
}
