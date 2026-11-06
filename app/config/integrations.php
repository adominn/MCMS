<?php

// The active implementation of each adapter is chosen here, never in code (IR-16). Modes: stub, manual, live.
return [
    'modes' => [
        'sso' => env('MCMS_ADAPTER_SSO', 'stub'),
        'email' => env('MCMS_ADAPTER_EMAIL', 'stub'),
        'payment' => env('MCMS_ADAPTER_PAYMENT', 'stub'),
        'learning_platform' => env('MCMS_ADAPTER_LEARNING_PLATFORM', 'manual'),
        'abc' => env('MCMS_ADAPTER_ABC', 'manual'),
        'qvc_mqa' => env('MCMS_ADAPTER_QVC_MQA', 'manual'),
        'sis' => env('MCMS_ADAPTER_SIS', 'manual'),
    ],
    'labels' => [
        'sso' => 'Single sign-on (Microsoft Entra ID)',
        'email' => 'Email (SMTP)',
        'payment' => 'Payment service',
        'learning_platform' => 'Learning platform (UCreds)',
        'abc' => 'Academic Bank of Credit',
        'qvc_mqa' => 'QVC / MQA',
        'sis' => 'Student information system',
    ],
];
