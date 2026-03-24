<?php
declare(strict_types=1);

return [
    'gymbeam' => [
        'name' => 'GymBeam',
        'aliases' => ['gym beam'],
        'hosts' => ['gymbeam.sk', 'gymbeam.cz', 'gymbeam.hu', 'gymbeam.ro'],
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
        'aliases' => ['vilgain'],
        'hosts' => ['aktin.sk', 'aktin.cz', 'vilgain.sk', 'vilgain.cz'],
        'network' => 'manual-or-other',
        'campaign_status' => 'manual',
        'feed_available' => false,
    ],
    'myprotein' => [
        'name' => 'Myprotein',
        'aliases' => ['my protein'],
        'hosts' => ['myprotein.sk', 'myprotein.cz', 'myprotein.com'],
        'network' => 'manual-or-other',
        'campaign_status' => 'manual',
        'feed_available' => false,
    ],
    'proteinsk' => [
        'name' => 'Protein.sk',
        'aliases' => ['protein.sk', 'protein sk', 'protein-sk'],
        'hosts' => ['protein.sk'],
        'network' => 'manual-or-other',
        'campaign_status' => 'manual',
        'feed_available' => false,
    ],
];
