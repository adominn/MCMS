# Finds the page of each heading in a rendered PDF so the second render can print TOC page numbers.
# Usage: py toc.py <rendered.pdf> <headings.json> <toc.json>
import json, re, sys, unicodedata
import pypdf

pdf, heads, out = sys.argv[1:4]
norm = lambda s: re.sub(r'\s+', '', unicodedata.normalize('NFKC', s)).lower()
pages = [norm(p.extract_text() or '') for p in pypdf.PdfReader(pdf).pages]
start = next(i for i, t in enumerate(pages) if 'tocendmarker' in t) + 1

result, missing = {}, []
for h in json.load(open(heads, encoding='utf-8')):
    n = norm(h)
    hit = next((i for i in range(start, len(pages)) if n in pages[i]), None)
    if hit is None:
        missing.append(h)
        continue
    result[h] = hit + 1
    start = hit  # headings appear in order; never search backwards
json.dump(result, open(out, 'w', encoding='utf-8'), ensure_ascii=False)
print(f'{len(result)} headings located, {len(missing)} missing', *missing, sep='\n  ')
