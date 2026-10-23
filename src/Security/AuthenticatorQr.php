<?php

namespace Pcteckserv\CmsCore\Security;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class AuthenticatorQr
{
    public function uri(AccountFactor $factor, string $email): string
    {
        $issuer = trim(str_replace(':', ' ', (string) config('app.name', 'CMS')));
        $issuer = mb_substr($issuer ?: 'CMS', 0, 100);

        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($email).'?'.http_build_query([
            'secret' => $factor->secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => 6, 'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function image(AccountFactor $factor, string $email): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(280, 4), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($this->uri($factor, $email), 'UTF-8'));
    }
}
