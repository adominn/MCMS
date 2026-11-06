# Writes the SRS requirement keys and their published IDs (FR-01, BR-05, ...) as a script fragment,
# so another document (the PRD) can cite {{fr-sso}} and get the SRS number. Numbering mirrors template.html.
# Usage: py srs_ids.py <srs src dir> <out fragment.html>
import json, re, sys
from pathlib import Path

src, out = Path(sys.argv[1]), Path(sys.argv[2])
counters, ids = {}, {}
for f in sorted(src.glob('*.html')):
    for m in re.finditer(r'<script type="text/req" data-p="(\w+)"[^>]*>(.*?)</script>', f.read_text(encoding='utf-8'), re.S):
        prefix = m.group(1)
        for line in m.group(2).split('\n'):
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            counters[prefix] = counters.get(prefix, 0) + 1
            ids[line.split('|')[0].strip()] = f'{prefix}-{counters[prefix]:02d}'
out.write_text(f'<script>window.__EXT_IDS = {json.dumps(ids)};</script>\n', encoding='utf-8')
print(f'{len(ids)} SRS ids written to {out}')
