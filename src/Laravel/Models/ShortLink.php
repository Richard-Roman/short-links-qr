<?php

namespace RichardRoman\ShortLinks\Laravel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RichardRoman\ShortLinks\Database\Factories\ShortLinkFactory;

class ShortLink extends Model
{
    /** @use HasFactory<ShortLinkFactory> */
    use HasFactory, HasUuids;

    public function getTable(): string
    {
        return (string) config('short-links.tables.short_links', 'short_links');
    }

    public $timestamps = true;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $fillable = [
        'codigo',
        'url_destino',
        'entidad_tipo',
        'entidad_id',
        'titulo',
        'creado_por',
        'activo',
        'total_clicks',
        'qr_storage_url',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'total_clicks' => 'integer',
            'creado_en' => 'datetime',
        ];
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ShortLinkClick::class, 'short_link_id');
    }

    protected static function newFactory(): ShortLinkFactory
    {
        return ShortLinkFactory::new();
    }
}
