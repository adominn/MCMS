<?php

return [
    // Test sign-in (FR-05). Must never be on in production; AppServiceProvider refuses to boot if it is.
    'issuer' => 'Universiti Teknikal Malaysia Melaka',

    'stub_idp' => (bool) env('MCMS_STUB_IDP', false),

    // Demo clock (PRD 7.2). Works only with the test sign-in and never in production.
    'demo_clock' => (bool) env('MCMS_DEMO_CLOCK', false),
];
