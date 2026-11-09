"""Atualiza os catálogos locais a partir das distribuições oficiais versionadas."""

import io
import json
from pathlib import Path
import re
import tarfile
import urllib.request
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parent
PACKAGES = [
    ('bootstrap', 'Bootstrap Icons', 'bootstrap-icons', '1.13.2'),
    ('fontawesome', 'Font Awesome Free', '@fortawesome/fontawesome-free', '7.3.1'),
]
TAGS = {'svg', 'g', 'path', 'circle', 'rect', 'ellipse', 'polygon', 'polyline', 'line'}
ATTRIBUTES = {
    'viewBox', 'fill', 'fill-rule', 'clip-rule', 'd', 'cx', 'cy', 'r', 'rx',
    'ry', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'width', 'height', 'points',
    'opacity', 'transform', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
}


def sanitize_svg(source):
    root = ET.fromstring(source)
    for node in root.iter():
        node.tag = node.tag.rsplit('}', 1)[-1]
        if node.tag not in TAGS:
            raise ValueError(f'Elemento SVG não permitido: {node.tag}')
        node.attrib = {key: value for key, value in node.attrib.items() if key in ATTRIBUTES}
        if any(re.search(r'url\s*\(|[<>]', value, re.I) for value in node.attrib.values()):
            raise ValueError('Atributo SVG não permitido')
    root.set('width', '20')
    root.set('height', '20')
    root.set('fill', 'currentColor')
    root.set('aria-hidden', 'true')
    root.set('focusable', 'false')
    root.set('xmlns', 'http://www.w3.org/2000/svg')
    return ET.tostring(root, encoding='unicode')


for library, label, package, version in PACKAGES:
    url = f'https://registry.npmjs.org/{package}/-/{package.split("/")[-1]}-{version}.tgz'
    with urllib.request.urlopen(url, timeout=60) as response:
        archive = tarfile.open(fileobj=io.BytesIO(response.read()), mode='r:gz')
    icons = {}
    for member in archive.getmembers():
        prefix = 'package/icons/' if library == 'bootstrap' else 'package/svgs/'
        if member.name.startswith(prefix) and member.name.endswith('.svg'):
            name = member.name[len(prefix):-4]
            if not re.fullmatch(r'[a-z0-9/-]+', name):
                raise ValueError('Nome de ícone inválido')
            icons[f'{library}:{name}'] = {
                'label': name.replace('/', ' / ').replace('-', ' '),
                'svg': sanitize_svg(archive.extractfile(member).read()),
            }
    license_member = next(m for m in archive.getmembers() if m.name in ('package/LICENSE', 'package/LICENSE.txt'))
    (ROOT / f'{library}-LICENSE.txt').write_bytes(archive.extractfile(license_member).read())
    catalog = {'label': label, 'version': version, 'source': url, 'icons': dict(sorted(icons.items()))}
    (ROOT / f'{library}.json').write_text(json.dumps(catalog, ensure_ascii=False, separators=(',', ':')), encoding='utf-8')
    print(f'{label} {version}: {len(icons)} ícones')
