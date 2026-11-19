<?php

namespace Pcteckserv\CmsCore\Support;

class VatRates
{
    /** Explicitly configured rates; no fiscal jurisdiction is inferred. */
    public function rates(): array
    {
        return $this->parse((string) app(SiteOptions::class)->get('vat_rates', ''));
    }

    public function parse(string $text): array
    {
        $rates = [];
        foreach (preg_split('/\R/', trim($text)) as $line) {
            $line = str_replace(',', '.', trim($line));
            if ($line === '') continue;
            if (! preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $line) || (float) $line > 100) {
                throw new \InvalidArgumentException('Indique uma taxa de IVA entre 0 e 100 por linha, com até duas casas decimais.');
            }
            $rate = number_format((float) $line, 2, '.', '');
            $rates[$rate] = $rate.'%';
        }
        if (count($rates) > 20) throw new \InvalidArgumentException('Configure no máximo 20 taxas de IVA.');
        return $rates;
    }
}
