<?php

namespace RichardRoman\ShortLinks\Laravel\Mappers;

use RichardRoman\ShortLinks\Core\Data\CreateShortLinkData;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink as ShortLinkValueObject;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink as ShortLinkModel;

final class ShortLinkMapper
{
    public function toValueObject(ShortLinkModel $model): ShortLinkValueObject
    {
        return new ShortLinkValueObject(
            id: (string) $model->getKey(),
            codigo: $model->codigo,
            urlDestino: $model->url_destino,
            entidadTipo: $model->entidad_tipo,
            entidadId: $model->entidad_id,
            titulo: $model->titulo,
            creadoPorId: $model->creado_por,
            activo: (bool) $model->activo,
            totalClicks: (int) $model->total_clicks,
            qrStorageUrl: $model->qr_storage_url,
        );
    }

    /** @return array<string, mixed> */
    public function toModelAttributes(CreateShortLinkData $data): array
    {
        return [
            'codigo' => $data->codigo,
            'url_destino' => $data->urlDestino,
            'titulo' => $data->titulo,
            'entidad_tipo' => $data->entidadTipo,
            'entidad_id' => $data->entidadId,
            'creado_por' => $data->creadoPorId,
            'activo' => true,
            'total_clicks' => 0,
        ];
    }
}
