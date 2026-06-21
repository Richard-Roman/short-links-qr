<?php

namespace RichardRoman\ShortLinks\Laravel\Repositories;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RichardRoman\ShortLinks\Contracts\RedirectCacheInterface;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Data\ClickData;
use RichardRoman\ShortLinks\Core\Data\CreateShortLinkData;
use RichardRoman\ShortLinks\Core\Exceptions\DuplicateCodeException;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;
use RichardRoman\ShortLinks\Laravel\Mappers\ShortLinkMapper;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink as ShortLinkModel;

final class EloquentShortLinkRepository implements ShortLinkRepositoryInterface
{
    public function __construct(
        private readonly ShortLinkMapper $mapper,
        private readonly RedirectCacheInterface $redirectCache,
    ) {}

    public function findActiveByCodigo(string $codigo): ?ShortLink
    {
        $model = ShortLinkModel::query()
            ->where('codigo', $codigo)
            ->where('activo', true)
            ->first();

        return $model === null ? null : $this->mapper->toValueObject($model);
    }

    public function findActiveByEntity(string $entidadTipo, string $entidadId): ?ShortLink
    {
        $model = ShortLinkModel::query()
            ->where('entidad_tipo', $entidadTipo)
            ->where('entidad_id', $entidadId)
            ->where('activo', true)
            ->first();

        return $model === null ? null : $this->mapper->toValueObject($model);
    }

    public function create(CreateShortLinkData $data): ShortLink
    {
        try {
            $model = ShortLinkModel::query()->create(
                $this->mapper->toModelAttributes($data),
            );
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw DuplicateCodeException::forCodigo($data->codigo);
            }

            throw $exception;
        }

        return $this->mapper->toValueObject($model);
    }

    public function incrementClicksAndRecord(ShortLink $shortLink, ClickData $clickData): void
    {
        DB::transaction(function () use ($shortLink, $clickData): void {
            $model = ShortLinkModel::query()->findOrFail($shortLink->id);
            $model->increment('total_clicks');

            $model->clicks()->create([
                'ip_hash' => $clickData->ipHash,
                'referrer' => $clickData->referrer,
                'user_agent' => $clickData->userAgent,
            ]);
        });
    }

    public function deactivateByCodigo(string $codigo): void
    {
        ShortLinkModel::query()
            ->where('codigo', $codigo)
            ->update(['activo' => false]);

        $this->redirectCache->forget($codigo);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array($sqlState, ['23505', '23000'], true);
    }
}
