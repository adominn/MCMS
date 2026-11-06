"""Wrap screen fragments (src/*.html) in the MCMS shell and inline the CSS -> out/*.html.

A fragment holds <section class="screen|phone" data-role=".." data-nav=".." data-name="..">main content</section>.
Tokens: {i:name} inlines an icon.
"""
import re, sys
from pathlib import Path

ROOT = Path(__file__).parent
CSS = (ROOT / "src/mcms.css").read_text(encoding="utf-8")

P = {  # 24-box outline paths, drawn at 16/20px
    "inbox": '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5 5h14l2 8v6H3v-6z"/>',
    "doc": '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
    "cal": '<path d="M4 6h16v14H4zM4 10h16M8 3v5M16 3v5"/>',
    "check": '<path d="M4 4h16v16H4z"/><path d="m8 12 3 3 5-6"/>',
    "badge": '<circle cx="12" cy="9" r="5"/><path d="m9 13.5-1.5 7L12 18l4.5 2.5-1.5-7"/>',
    "shield": '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/>',
    "bank": '<path d="M3 9 12 4l9 5M5 10v8M10 10v8M14 10v8M19 10v8M3 20h18"/>',
    "swap": '<path d="M4 8h14l-4-4M20 16H6l4 4"/>',
    "layers": '<path d="m12 4 9 5-9 5-9-5z"/><path d="m3 14 9 5 9-5"/>',
    "chart": '<path d="M4 20V4M4 20h16M8 16v-5M12 16V8M16 16v-3"/>',
    "users": '<circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.5 2.7-6 6-6s6 2.5 6 6M16 4.5a3.5 3.5 0 0 1 0 7M21 20c0-3-1.7-5.3-4-6"/>',
    "gear": '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
    "sync": '<path d="M20 11a8 8 0 0 0-14.5-4M4 13a8 8 0 0 0 14.5 4M5 3v4h4M19 21v-4h-4"/>',
    "plug": '<path d="M9 3v5M15 3v5M6 8h12v3a6 6 0 0 1-12 0zM12 17v4"/>',
    "list": '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
    "home": '<path d="m3 11 9-7 9 7M5 9.5V20h14V9.5"/>',
    "wallet": '<path d="M3 7h18v13H3zM3 7l13-3v3M16 13.5h2"/>',
    "user": '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    "search": '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/>',
    "clip": '<path d="M9 4h6v3H9zM6 5.5H4V21h16V5.5h-2M8 12h8M8 16h5"/>',
    "menu": '<path d="M4 7h16M4 12h16M4 17h16"/>',
    "back": '<path d="M15 5 8 12l7 7"/>',
    "share": '<path d="M12 15V3M7 8l5-5 5 5M5 13v8h14v-8"/>',
    "down": '<path d="m6 9 6 6 6-6"/>',
    "right": '<path d="m9 6 6 6-6 6"/>',
    "audit": '<path d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h3"/>',
}
def icon(name, size=16, color="#525252"):
    return (f'<svg width="{size}" height="{size}" viewBox="0 0 24 24" fill="none" stroke="{color}" '
            f'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{P[name]}</svg>')

USERS = {
    "dd": ("Hakim Rahman", "Deputy dean (Academic), FTMK"),
    "lec": ("Dr. Aina Zulkifli", "Lecturer, FTMK"),
    "codl": ("Farid Hashim", "System administrator, CODL"),
}
NAV = {
    "dd": [("Work", None), ("queue", "inbox", "Task queue", "10"), ("proposals", "doc", "Proposals", "2"),
           ("offerings", "cal", "Offerings and readiness", "1"), ("results", "check", "Results to verify", "2"),
           ("creds", "badge", "Credentials", ""), ("quality", "shield", "Quality and QV", "1"),
           ("acr", "bank", "ACR and ABC deposits", "3"), ("transfer", "swap", "Credit transfer", "1"),
           ("stack", "layers", "Stackable pathways", ""), ("Insight", None), ("reports", "chart", "Reports", "")],
    "lec": [("Teaching", None), ("mine", "home", "My micro-credentials", ""), ("proposals", "doc", "Proposals", "1"),
            ("reviews", "clip", "Reviews for colleagues", "1"), ("cohorts", "users", "Cohorts and results", "1"),
            ("Quality", None), ("cqi", "chart", "Quality review (CQI)", "1"), ("qv", "shield", "QV evidence", "")],
    "codl": [("Administration", None), ("users", "users", "Users and roles", ""), ("settings", "gear", "Settings", ""),
             ("templates", "doc", "Templates", ""), ("Data", None), ("ucreds", "sync", "UCreds exchange", ""),
             ("adapters", "plug", "Adapter status", "1"), ("audit", "audit", "Audit trail", "")],
}
PNAV = [("home", "home", "Learning"), ("creds", "badge", "Credentials"), ("ledger", "bank", "Credit"), ("me", "user", "Profile")]

def top(role):
    name, post = USERS[role]
    return (f'<div class="top"><div class="brand"><b>UTeM MCMS</b><span>Micro-Credential Management</span></div>'
            f'<div class="scope"><span class="lang"><span class="on">EN</span><span>BM</span></span>'
            f'<span><span class="who">{name}</span> · {post}</span></div></div>')

def side(role, active):
    out = []
    for item in NAV[role]:
        if item[1] is None:
            out.append(f'<div class="grp">{item[0]}</div>')
            continue
        key, ic, label, count = item
        on = key == active
        c = f'<span class="c">{count}</span>' if count not in ("", "0") else ""
        out.append(f'<div class="nav{" on" if on else ""}">{icon(ic, 16, "#161616" if on else "#525252")}{label}{c}</div>')
    return '<div class="side">' + "".join(out) + "</div>"

def public_top():
    return ('<div class="top"><div class="brand"><b>UTeM</b><span>Micro-credentials</span></div>'
            '<div class="scope"><span>Catalogue</span><span>Verify a credential</span>'
            '<span class="lang"><span class="on">EN</span><span>BM</span></span><span class="who">Sign in</span></div></div>')

def pnav(active):
    return '<div class="pnav">' + "".join(
        f'<div class="{"on" if k == active else ""}">{icon(ic, 20, "#161616" if k == active else "#525252")}{label}</div>'
        for k, ic, label in PNAV) + "</div>"

SEC = re.compile(r'<section class="(screen|phone|sheet)"([^>]*)>(.*?)</section>', re.S)
ATTR = re.compile(r'data-(\w+)="([^"]*)"')

def wrap(m):
    kind, attrs, inner = m.group(1), dict(ATTR.findall(m.group(2))), m.group(3)
    role, nav, name = attrs.get("role", ""), attrs.get("nav", ""), attrs.get("name", "")
    if kind == "sheet":
        return f'<section class="sheet" data-name="{name}">{inner}</section>'
    if kind == "phone":
        title = attrs.get("title", "")
        head = (f'<div class="ptop">{icon(attrs.get("lead", "menu"), 20, "#161616")}<b>{title}</b>'
                f'<span class="small muted" style="margin-left:auto">Amir Hakimi</span></div>')
        return f'<section class="phone" data-name="{name}">{head}<div class="pmain">{inner}</div>{pnav(nav)}</section>'
    if role == "public":
        return f'<section class="screen" data-name="{name}">{public_top()}<div class="body"><div class="main">{inner}</div></div></section>'
    return (f'<section class="screen" data-name="{name}">{top(role)}<div class="body">{side(role, nav)}'
            f'<div class="main">{inner}</div></div></section>')

def build(src):
    html = SEC.sub(wrap, src.read_text(encoding="utf-8"))
    html = re.sub(r"\{i:(\w+)(?::(\d+))?\}", lambda m: icon(m.group(1), int(m.group(2) or 16)), html)
    # keep a category mark beside its name when the name wraps
    html = re.sub(r'<span class="cat">(\w+)</span>\s*([^<]+)',
                  r'<span class="nm"><span class="cat">\1</span><span>\2</span></span>', html)
    doc = (f'<!doctype html><html><head><meta charset="utf-8"><title>{src.stem}</title>'
           f'<style>{CSS}</style></head><body>{html}</body></html>')
    out = ROOT / "out" / src.name
    out.parent.mkdir(exist_ok=True)
    out.write_text(doc, encoding="utf-8")
    assert "{i:" not in doc, f"unresolved icon token in {src.name}"
    print(out.name, len(SEC.findall(src.read_text(encoding="utf-8"))), "screens")

for f in sorted((ROOT / "src").glob(sys.argv[1] if len(sys.argv) > 1 else "*.html")):
    build(f)
