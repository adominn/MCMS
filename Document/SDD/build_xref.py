"""Cross-reference check of the SDD sources: every cited AD/DA/MD/EV/JB ID and every table name exists.
Usage: py SDD/build_xref.py"""
import re
from pathlib import Path

src = {p.name: p.read_text(encoding='utf-8') for p in sorted(Path('SDD/src').glob('*.html'))}
allt = '\n'.join(src.values())
defined = {
    'AD': set(re.findall(r'^(AD-\d+) \|', src['02-intro.html'], re.M)),
    'DA': set(re.findall(r'^(DA-\d+) \|', src['11-verification.html'], re.M)),
    'EV': set(re.findall(r'^(EV-\d+) \|', src['05-modules.html'], re.M)),
    'JB': set(re.findall(r'^(JB-\d+) \|', src['05-modules.html'], re.M)),
    'MD': {f'MD-{i:02d}' for i in range(17)},
}
problems = 0
for kind, ids in defined.items():
    cited = set(re.findall(rf'\b{kind}-\d+\b', allt))
    missing = sorted(cited - ids, key=lambda x: int(x.split('-')[1]))
    if missing: problems += 1; print(f'{kind} cited but not defined: {missing}')
# event/job names must keep the same number everywhere
names = {}
for m in re.finditer(r'\b([A-Z][A-Za-z]+) \(((?:EV|JB)-\d+)\)', allt): names.setdefault(m.group(1), set()).add(m.group(2))
clash = {k: v for k, v in names.items() if len(v) > 1}
if clash: problems += 1; print('names with two numbers:', clash)
# tables: names in <code> that look like tables vs the catalogue in 06-data
cat = set(re.findall(r'^<code>([a-z_]+)</code>', src['06-data.html'], re.M)) | set(re.findall(r'^([a-z][a-z_]+)\*? \|', src['06-data.html'], re.M))
used = set(re.findall(r'<code>([a-z]+_[a-z_]+s)</code>', allt))
unknown = sorted(t for t in used - cat if not t.startswith(('app_', 'mcms_', 'eddsa_')))
print('tables in catalogue:', len(cat), '| table-like names used but not catalogued:', unknown[:40])
print('OK' if not problems else f'{problems} problem groups')
