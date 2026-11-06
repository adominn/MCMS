<?php

// Guide Appendix C.
return [
    'domains' => ['content' => 'Content currency', 'delivery' => 'Delivery', 'assessment' => 'Assessment and integrity', 'platform' => 'Platform and access', 'support' => 'Participant support', 'outcomes' => 'Learning outcome attainment'],
    'feedback_areas' => ['content' => 'Content', 'activities' => 'Learning activities', 'assessment' => 'Assessment', 'platform' => 'Platform', 'support' => 'Support', 'relevance' => 'Relevance'],
    // The feedback form groups what a participant can rate. The six areas above are required; the rest are optional (1 to 5).
    'feedback_groups' => [
        'Content' => ['content' => 'Content overall', 'clarity' => 'Clarity of the learning outcomes', 'depth' => 'Depth and level of the content'],
        'Learning activities' => ['activities' => 'Learning activities overall', 'practice' => 'Practice, labs and examples'],
        'Assessment' => ['assessment' => 'Assessment overall', 'fairness' => 'Fairness of the assessment', 'feedback_quality' => 'Feedback on your work'],
        'Platform' => ['platform' => 'Platform overall', 'ease' => 'Ease of finding and using the materials', 'reliability' => 'Reliability of the platform'],
        'Support' => ['support' => 'Support overall', 'lecturer' => 'Lecturer responsiveness', 'admin' => 'CODL and administration'],
        'Relevance' => ['relevance' => 'Relevance overall', 'workload' => 'Workload for the time given', 'recommend' => 'You would recommend it to others'],
    ],
    'priority' => ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'],
    'action_status' => ['not_started' => 'Not started', 'in_progress' => 'In progress', 'done' => 'Done (effectiveness pending)', 'closed' => 'Closed', 'carried' => 'Carried to the next cycle'],
    'effectiveness' => ['effective' => 'Effective', 'partly' => 'Partly effective', 'not_effective' => 'Not effective'],
    'confirmations' => ['prepared' => 'The record was prepared from the evidence of this cohort', 'evidence' => 'The evidence for each issue and action is attached or referenced', 'owners' => 'Every action has an owner, a due date and a success indicator'],
    'review_outcome' => ['continue' => 'Continue without significant change', 'minor_cqi' => 'Continue with minor CQI', 'content_review' => 'Content or assessment review needed', 'reapproval' => 'Review and re-approval needed', 'suspension' => 'Consider suspension or termination'],
    'transition' => ['completion_window' => 'Current participants finish within a completion window', 'new_version' => 'Participants move to a new version', 'equivalence' => 'Earlier achievement gets an equivalence decision'],
];
