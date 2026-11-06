"""Aligns event and job names in SDD sections with the catalogue of Section 4 (05-modules.html).
Adds the EV/JB number after each known name, and replaces names the catalogue merged or does not model as events.
Usage: py SDD/build_align.py SDD/src/08-processes.html [more files]"""
import re, sys
from pathlib import Path

mods = Path('SDD/src/05-modules.html').read_text(encoding='utf-8')
cat = {}
for m in re.finditer(r'^(EV-\d+|JB-\d+) \| ([A-Za-z]+)', mods, re.M):
    cat.setdefault(m.group(2), m.group(1))
assert len([k for k, v in cat.items() if v.startswith('EV')]) >= 60, 'event catalogue not found'

# names used elsewhere that the catalogue expresses differently
MERGED = {
    'VersionApproved': 'SenateDecisionRecorded', 'VersionNotApproved': 'SenateDecisionRecorded', 'MappingApproved': 'SenateDecisionRecorded',
    'QvResultRecorded': 'QvVerified or QvNotVerified', 'ConsentRequested': 'AcrConfirmed', 'CreditProvisionallyRedeemed': 'TransferDecided',
    'BadgeFlagged': 'BadgeImported', 'BadgeMatched': 'BadgeImported',
}
NOT_EVENTS = ['ReviewCommentAnswered', 'EnrolmentCancelled', 'PaymentDeclined', 'AcrChecksRecorded', 'AwardRecorded', 'DepositExportCreated',
              'EquivalenceEvaluated', 'MarksSaved', 'OfferingCreated', 'ReadinessItemSaved', 'ResultsFileUploaded', 'StatementsAcknowledged',
              'ValidityChecked', 'VersionDraftCreated', 'QvExpiryAlertDue']

def fix(text):
    for old, new in MERGED.items():
        text = re.sub(rf'\b{old}\b', new, text)
    for name in NOT_EVENTS:  # internal steps: recorded in the audit trail, not published as events
        text = re.sub(rf'\b{name}\b(?! \()', f'{name} (audit entry)', text)
    text = re.sub(r'\b([A-Z][a-z]+(?:[A-Z][a-z]+)*?)(NoticeQueued|TaskCreated)\b(?! \()', lambda m: m.group(0) + ' (audit entry)', text)
    for name, nid in sorted(cat.items(), key=lambda kv: -len(kv[0])):  # longest first so IssueCredentialJob does not hit IssueCredentialsBatchJob
        text = re.sub(rf'\b{name}\b(?! \((?:EV|JB)-)', f'{name} ({nid})', text)
    return text

for f in sys.argv[1:]:
    p = Path(f); before = p.read_text(encoding='utf-8'); after = fix(before)
    p.write_text(after, encoding='utf-8')
    print(f, 'tagged', len(re.findall(r'\((?:EV|JB)-\d+\)', after)) - len(re.findall(r'\((?:EV|JB)-\d+\)', before)))
