/* Cross-role flows. Role builders register their own flows in their screen files. */
(function () {
MC.flow({ id: 'FL-19', title: 'End-to-end: Amir from catalogue to credit used in a degree', actor: 'All roles', summary: 'SRS use case A.5 and PRD demo scenario: every recognition stage as its own decision',
  steps: [
    { go: '#/public/home', say: 'Amir, a working engineer, finds UTeM micro-credentials on the public site.' },
    { go: '#/public/offering/CND-2027-1', say: 'The offering says what he will get and that completion, ACR, ABC deposit and transfer are separate decisions.' },
    { go: '#/st/register/CND-2027-1', say: 'He signs in with his UTeM learner account, accepts the recognition statement and PDPA notice, and pays (dummy service).' },
    { go: '#/st/home', say: 'He studies on UCreds. MCMS shows his module progress across months.' },
    { go: '#/codl/ucreds', say: 'CODL imports results from UCreds. Bad rows are rejected; good rows stay Provisional.' },
    { go: '#/lec/cohort/CND-2026-2', say: 'Dr. Aina fills the two missing marks and sends the cohort for verification.' },
    { go: '#/dd/verify/DS-2026-2', say: 'Hakim verifies results. This creates module badges only, nothing else. (Shown on Data Structures 2026/2; Amir\'s own cohorts were verified the same way.)' },
    { go: '#/dd/completion/LE-2026-10', say: 'Hakim confirms completion as a separate step. The system checks each learner and issues Amir\'s Leadership Essentials certificate. It is an MCE, so no ACR follows.' },
    { go: '#/st/credentials', say: 'Amir sees his credentials, including the new Leadership Essentials certificate. He opens one and copies a share link.' },
    { go: '#/public/verify/3HXP-92LD', say: 'An employer opens the link without signing in and sees only name, credential, issuer, date and status.' },
    { go: '#/st/acr/MCMS-A-2026-000214', say: 'Amir gives consent to deposit his ACR in the Academic Bank of Credit.' },
    { go: '#/dd/deposit/MCMS-A-2026-000214', say: 'Hakim sees the consented ACR in the deposit queue and records the ABC reference.' },
    { go: '#/st/ledger', say: 'Amir\'s ledger shows the credit Available for 10 years. It is storage, not a transfer.' },
    { go: '#/st/transfer/new', say: 'Admitted to the degree (a separate decision), Amir can now apply with Cloud Native Development. Data Structures is already in his earlier application TR-2026-0012, and MCE credit is never selectable.' },
    { go: '#/dd/transfer/TR-2026-0012', say: 'Hakim, deputy dean of the degree\'s faculty, decides Amir\'s earlier application on learning outcomes and approves. The credit becomes Provisionally redeemed.' },
    { go: '#/dd/plan/SP-2026-003', say: 'The stackable plan checks that at least 30% of credit is still earned at UTeM, and residency.' },
    { go: '#/dd/trace/amir', say: 'Anyone authorised can trace every record from approval to credit use, with who and when.' },
  ] });
})();
