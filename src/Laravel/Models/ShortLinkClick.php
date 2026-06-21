<?php

namespace RichardRoman\ShortLinks\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortLinkClick extends Model
{
    public function getTable(): string
    {
        return (string) config('short-links.tables.short_link_clicks', 'short_link_clicks');
    }

    public $timestamps = true;

    public const CREATED_AT = 'clicked_en';

    public const UPDATED_AT = null;

    protected $fillable = [
        'short_link_id',
        'ip_hash',
        'referrer',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'clicked_en' => 'datetime',
        ];
    }

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class, 'short_link_id');
    }
}
