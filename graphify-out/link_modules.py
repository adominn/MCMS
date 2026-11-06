# Links the SDD module nodes (MD-00 to MD-16) to the code in app/app/Modules/<folder>, so the document graph and the
# code graph connect. Re-run after every `/graphify --update` (an update re-extracts code files and drops these edges):
#   & (Get-Content graphify-out\.graphify_python) -I graphify-out\link_modules.py
import json, re
from pathlib import Path
from networkx.readwrite import json_graph
from graphify.cluster import score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate
from graphify.export import to_json

ROOT = Path(__file__).resolve().parent.parent
# folder -> module id; each folder's PHP comments name its id, Identity is MD-01 per app/README.md
MODULES = {'Core': 'MD-00', 'Identity': 'MD-01', 'Curriculum': 'MD-02', 'Approval': 'MD-03', 'Offering': 'MD-04', 'Enrolment': 'MD-05',
           'Learning': 'MD-06', 'Assessment': 'MD-07', 'Achievement': 'MD-08', 'Credentials': 'MD-09', 'Quality': 'MD-10', 'CreditBank': 'MD-11',
           'Transfer': 'MD-12', 'Pathways': 'MD-13', 'Monitoring': 'MD-14', 'Records': 'MD-15', 'Notifications': 'MD-16'}

data = json.loads((ROOT / 'graphify-out/graph.json').read_text(encoding='utf-8'))
G = json_graph.node_link_graph(data, edges='links')
src = lambda n: str(G.nodes[n].get('source_file') or '').replace('\\', '/')
md_nodes = {}
for n, d in G.nodes(data=True):
    m = re.match(r'^(MD-\d\d)\b', d.get('label', ''))
    if m and d.get('file_type') != 'code':
        md_nodes.setdefault(m.group(1), []).append(n)

added = 0
for folder, md in MODULES.items():
    targets = md_nodes.get(md, [])
    files = [n for n in G.nodes if src(n).startswith(f'app/app/Modules/{folder}/') and G.nodes[n].get('label', '').endswith('.php')]
    for f in files:
        text = (ROOT / src(f)).read_text(encoding='utf-8', errors='replace')
        named = md in text
        for t in targets:
            if not G.has_edge(f, t):
                G.add_edge(f, t, relation='implements', confidence='EXTRACTED' if named else 'INFERRED', confidence_score=1.0 if named else 0.95,
                           source_file=src(f), weight=1.0, _origin='manual_link')
                added += 1
print(f'added {added} module links ({len(md_nodes)} module ids found in the documents)')

# keep the existing communities and their labels; only the edges and the analysis change
labels = {int(k): v for k, v in json.loads((ROOT / 'graphify-out/.graphify_labels.json').read_text(encoding='utf-8')).items()}
com = {}
for n, d in G.nodes(data=True):
    com.setdefault(int(d.get('community', -1)), []).append(n)
com.pop(-1, None)
coh = score_all(G, com)
det = {'total_files': 459, 'total_words': 1397287, 'files': {}, 'skipped_sensitive': []}
report = generate(G, com, coh, labels, god_nodes(G), surprising_connections(G, com), det, {'input': 0, 'output': 0}, str(ROOT),
                  suggested_questions=suggest_questions(G, com, labels))
(ROOT / 'graphify-out/GRAPH_REPORT.md').write_text(report, encoding='utf-8')
print('graph.json written:', to_json(G, com, str(ROOT / 'graphify-out/graph.json'), community_labels=labels))
