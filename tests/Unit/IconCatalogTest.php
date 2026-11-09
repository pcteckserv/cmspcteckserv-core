<?php

namespace Tests\Unit;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Pcteckserv\CmsCore\Support\IconCatalog;

class IconCatalogTest extends TestCase
{
    public function test_svg_locais_contem_apenas_geometria_segura(): void
    {
        $tags = ['svg', 'g', 'path', 'circle', 'rect', 'ellipse', 'polygon', 'polyline', 'line'];
        $attributes = ['viewBox', 'fill', 'fill-rule', 'clip-rule', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'width', 'height', 'points', 'opacity', 'transform', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'aria-hidden', 'focusable', 'xmlns'];
        $invalid = [];

        foreach (IconCatalog::libraries() as $key => $library) {
            foreach ($library['icons'] as $name => $icon) {
                $document = new DOMDocument;
                if (! $document->loadXML($icon['svg'], LIBXML_NONET) || ! str_starts_with($name, $key.':')) {
                    $invalid[] = $name;
                    continue;
                }
                foreach ($document->getElementsByTagName('*') as $element) {
                    if (! in_array($element->tagName, $tags, true)) {
                        $invalid[] = $name;
                    }
                    foreach ($element->attributes as $attribute) {
                        if (! in_array($attribute->name, $attributes, true) || preg_match('/url\s*\(|[<>]/i', $attribute->value)) {
                            $invalid[] = $name;
                        }
                    }
                }
            }
        }

        $this->assertSame([], $invalid);
    }
}
