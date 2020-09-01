<?php

namespace ROTGP\RestEasy\Test\Models;

class Play extends BaseModel
{
    protected $fillable = [
        'song_id', 'user_id', 'streaming_service_id', 'listen_time'
    ];

    // protected $validationRules = [
    //     'name' => 'required|unique',
    //     'biography' => 'required|unique|max:500',
    //     'record_label_id' => 'required|integer|exists',
    //     'fan_mail_address' => 'nullable'
    // ];

    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function streamingService()
    {
        return $this->belongsTo(StreamingService::class);
    }

    public function canRead($authUser)
    {
        // dd('play canRead? ', $authUser);
        return true;
    }
}
