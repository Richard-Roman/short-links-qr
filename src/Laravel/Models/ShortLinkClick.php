<?php

namespace RichardRoman\ShortLinks\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortLinkClick extends Model
{
    protected $table = 'short_link_clicks';

    public $timestamps = false;

    public const CREATED_AT = 'clicked_en';

    protected $fillable = [
        'short_link_id',
        'ip_hash',
        'referrer',
        'user_agent',
    ];

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class, 'short_link_id');
    }
}
