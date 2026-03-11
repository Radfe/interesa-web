<?php
declare(strict_types=1);

return [
    'gymbeam' => [
        'name' => 'GymBeam',
        'network' => 'dognet',
        'campaign_status' => 'approved',
        'campaign_public_url' => 'https://www.dognet.sk/kampane/kampan-gymbeam-sk/',
        'tracking' => 's2s',
        'cookie_days' => 10,
        'validation_days' => 75,
        'available_markets' => ['sk'],
        'feed_available' => true,
        'promotional_assets' => ['xml-feed', 'banners', 'coupons', 'pr-articles', 'email-templates'],
        'notes' => [
            'clean-internal-links-only',
            'keep-all-dognet-tracking-parameters',
            'direct-ppc-restricted',
        ],
    ],
    'aktin' => [
        'name' => 'Aktin',
        'network' => 'manual-or-other',
        'campaign_status' => 'manual',
        'feed_available' => false,
    ],
    'myprotein' => [
        'name' => 'Myprotein',
        'network' => 'manual-or-other',
        'campaign_status' => 'manual',
        'feed_available' => false,
    ],
    'proteinsk' => [
        'name' => 'Protein.sk',
        'network' => 'manual-or-other',
        'campaign_status' => 'manual',
        'feed_available' => false,
    ],
];