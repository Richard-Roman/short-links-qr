<?php

namespace RichardRoman\ShortLinks\Core\Validators;

use RichardRoman\ShortLinks\Contracts\UrlValidatorInterface;

final class HttpUrlValidator implements UrlValidatorInterface
{
    public function validate(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }
}
