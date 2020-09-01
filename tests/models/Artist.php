<?php

namespace ROTGP\RestEasy\Test\Models;

class Artist extends BaseModel
{
    protected $fillable = [
        'name', 'biography', 'record_label_id', 'fan_mail_address'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'record_label_id' => 'int',
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
            return ['record_label', 'users', 'albums'];
        } else {
            return ['record_label', 'albums'];
        }
    }

    public function safeScopes($authUser)
    {
        // dd('hmmm depends... ', $authUser->role->name);
        return ['record_label'];
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

    // public function scopeImplicit($query, $authUser, $payload, $queryParams)
    // {
    //     $query->where('id', '<', 10);
    // }

    public function scopeRecordLabel($query, $recordLabelId)
    {
        return $query->where('record_label_id', $recordLabelId);
    }

    public function canRead($authUser)
    {
        return true;
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
