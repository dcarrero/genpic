<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailSend extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'campaign_id', 'user_id', 'email', 'detected_language',
        'status', 'sent_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MailCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
