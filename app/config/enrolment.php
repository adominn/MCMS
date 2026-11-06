<?php

return [
    'pdpa_version' => '1.0',
    'pdpa' => [
        'en' => 'UTeM collects your name, contact details and learning records to run this micro-credential, issue credentials and keep the records the Guide requires. They are shared only as the Personal Data Protection Act 2010 allows. You can ask the CODL office to see or correct them.',
        'ms' => 'UTeM mengumpul nama, butiran perhubungan dan rekod pembelajaran anda untuk menjalankan micro-credential ini, mengeluarkan kredensial dan menyimpan rekod yang dikehendaki oleh Garis Panduan. Ia dikongsi hanya seperti yang dibenarkan oleh Akta Perlindungan Data Peribadi 2010. Anda boleh meminta pejabat CODL untuk melihat atau membetulkannya.',
    ],
    // Statuses that hold a place in the cohort (DA-10: a place is counted from Registered).
    'holds_place' => ['registered', 'pending_payment', 'enrolled', 'completed', 'not_completed'],
    'status' => ['registered' => 'Registered', 'pending_payment' => 'Pending payment', 'enrolled' => 'Enrolled', 'withdrawn' => 'Withdrawn', 'cancelled' => 'Cancelled', 'completed' => 'Completed', 'not_completed' => 'Not completed'],
    'test_decline_card' => '4000000000000002', // the dummy payment service declines this card (FL-06b)
];
