<?php
/**
 * All portfolio content lives here — edit this file to update the site.
 */
date_default_timezone_set('Asia/Kolkata');

return [
    'name'      => 'Vikas Rambaul Giri',
    'short'     => 'Vikas',
    'title'     => 'Digital Marketing Specialist & SEO Expert',
    'email'     => 'vikasr.giri@outlook.com',
    'phone'     => '+91 81608 83497',
    'phone_raw' => '918160883497',
    'location'  => 'Umbergaon, Valsad, Gujarat 396165, India',
    'city'      => 'Valsad, Gujarat',
    'notice'    => '1-month notice period',
    'resume'    => 'Vikas_Rambaul_Giri_Resume.pdf',
    'linkedin'  => '', // e.g. https://www.linkedin.com/in/your-handle
    'booking_url' => '', // Optional: Calendly or Google Calendar appointment link (shown inside the "Book a Call" app)
    // Book a Call app: interview slots in IST (24h), how many working days to offer, minimum notice
    'call_slots'        => ['10:00', '11:00', '12:00', '14:30', '15:30', '16:30', '17:30'],
    'call_days'         => 10,
    'call_notice_hours' => 2,
    'call_minutes'      => 30,

    'summary' => 'Digital Marketing Specialist & SEO Expert with 3+ years in B2B and export marketing. I rank websites, run Google & Meta campaigns, and turn IndiaMART, Alibaba.com and TradeIndia into steady pipelines of qualified buyers — and I write the code for the pages that convert them.',

    'queries' => [
        'b2b digital marketing specialist india',
        'seo expert for export business',
        'alibaba gold supplier account manager',
    ],

    'platforms' => ['Google Ads', 'Meta Ads', 'Google Analytics', 'Search Console', 'IndiaMART', 'Alibaba.com', 'TradeIndia', 'WordPress', 'Meta Business Suite'],

    'kpis' => [
        ['value' => 200, 'prefix' => '+', 'suffix' => '%', 'label' => 'Organic traffic',       'note' => 'Panchal Plastic Machinery', 'trend' => 'up'],
        ['value' => 3,   'prefix' => '',  'suffix' => '+', 'label' => 'Years in B2B marketing', 'note' => 'Industrial, textile & export', 'trend' => 'flat'],
        ['value' => 3,   'prefix' => '',  'suffix' => '',  'label' => 'B2B portals managed',    'note' => 'IndiaMART · Alibaba · TradeIndia', 'trend' => 'flat'],
        ['value' => 4,   'prefix' => '',  'suffix' => '',  'label' => 'Companies served',       'note' => 'Plus freelance clients', 'trend' => 'flat'],
    ],

    'cases' => [
        [
            'id'       => 'seo',
            'tab'      => '+200% organic',
            'client'   => 'Panchal Plastic Machinery Pvt. Ltd.',
            'period'   => '06/2023 — 11/2025',
            'title'    => 'Tripled organic traffic for an industrial machinery manufacturer',
            'challenge'=> 'A B2B machinery brand needed more qualified buyers finding it through search, not just through referrals.',
            'actions'  => [
                'Keyword research and on-page SEO across product and blog pages',
                'Technical SEO: site structure, internal linking, mobile deployment with Google Maps API',
                'Redesigned the site layout in HTML, CSS, JavaScript & Bootstrap',
                'CTA landing pages for lead capture, backed by Google Ads & Meta Ads',
            ],
            'metric'   => ['+200%', 'organic traffic growth'],
            'tags'     => ['SEO', 'Content', 'Web redesign', 'Google Ads'],
            'visual'   => 'chart',
        ],
        [
            'id'       => 'migration',
            'tab'      => 'WP → PHP migration',
            'client'   => 'Arizone International',
            'period'   => '08/2026 — Present',
            'title'    => 'Migrated the company website from WordPress to PHP — and protected its rankings',
            'challenge'=> 'Spam URLs had found their way into Google\'s index, putting the domain\'s search reputation at risk.',
            'actions'  => [
                'Rebuilt the website from WordPress into a custom PHP site',
                'Identified and removed spam URLs from Google\'s index',
                'Kept search visibility intact through the migration',
                'Ongoing keyword and competitor analysis to grow rankings',
            ],
            'metric'   => ['WP → PHP', 'migration with spam URLs de-indexed'],
            'tags'     => ['Technical SEO', 'Search Console', 'PHP', 'Migration'],
            'visual'   => 'migration',
        ],
        [
            'id'       => 'b2b',
            'tab'      => 'Export lead engine',
            'client'   => 'Arizone International',
            'period'   => '08/2026 — Present',
            'title'    => 'Running the B2B portals that bring in global buyers',
            'challenge'=> 'Export inquiries arrive from several marketplaces and need to be captured, qualified and followed up fast.',
            'actions'  => [
                'Manage the Alibaba.com Gold Supplier Plus account and OEM/ODM product listings',
                'Handle IndiaMART lead management for business development',
                'Track every inquiry through CRM for faster, cleaner follow-up',
                'Align SEO, Google Ads and Meta Ads with the sales pipeline',
            ],
            'metric'   => ['Gold Supplier+', 'Alibaba.com account managed'],
            'tags'     => ['Alibaba.com', 'IndiaMART', 'CRM', 'Lead generation'],
            'visual'   => 'portals',
        ],
        [
            'id'       => 'landing',
            'tab'      => 'Landing pages & ads',
            'client'   => 'DG Knitting, Surat',
            'period'   => '02/2026 — 06/2026',
            'title'    => 'Conversion-first landing pages paired with paid campaigns',
            'challenge'=> 'A textile brand expanding its reach needed paid traffic to land on pages built to convert.',
            'actions'  => [
                'Built landing pages with strong CTAs in HTML, CSS, JavaScript & Bootstrap',
                'Managed Google Ads & Meta Ads campaigns for lead generation and ROI',
                'Optimised product pages and blogs with on-page and technical SEO',
                'Applied lead nurturing and CRM strategies for acquisition and retention',
            ],
            'metric'   => ['SEO + Ads', 'one funnel, built to convert'],
            'tags'     => ['Landing pages', 'Google Ads', 'Meta Ads', 'CRO'],
            'visual'   => 'funnel',
        ],
    ],

    'funnel' => [
        ['stage' => 'Get found',  'kpi' => 'Impressions', 'desc' => 'Show up where B2B buyers search — on Google and on the marketplaces they trust.', 'skills' => ['Keyword research', 'On-page SEO', 'Technical SEO', 'Off-page SEO', 'Content writing', 'Competitor analysis']],
        ['stage' => 'Get clicks', 'kpi' => 'Traffic',     'desc' => 'Paid and social campaigns that put the right offer in front of the right audience.', 'skills' => ['Google Ads', 'Meta Ads', 'Social media', 'Email marketing', 'Blog content']],
        ['stage' => 'Convert',    'kpi' => 'Leads',       'desc' => 'Fast pages with clear CTAs that turn visits into inquiries.', 'skills' => ['Landing pages', 'HTML / CSS / JS', 'Bootstrap', 'WordPress', 'PHP', 'Google Analytics']],
        ['stage' => 'Close',      'kpi' => 'Customers',   'desc' => 'Capture, qualify and follow up every inquiry until it becomes business.', 'skills' => ['IndiaMART', 'Alibaba.com', 'TradeIndia', 'CRM', 'Lead nurturing']],
    ],

    'experience' => [
        [
            'company' => 'Arizone International',
            'role'    => 'Digital Marketing Executive',
            'period'  => '08/2026 — Present',
            'type'    => 'Full-time',
            'current' => true,
            'points'  => [
                'Manage digital marketing across SEO, Google Ads, Meta Ads and social media.',
                'Keyword research and competitor analysis to lift rankings and online visibility.',
                'Migrated the company website from WordPress to PHP and cleaned spam URLs from Google\'s index.',
                'Handle B2B lead generation and IndiaMART lead management.',
                'Manage the Alibaba.com Gold Supplier Plus account, OEM/ODM listings and global B2B leads.',
                'Run CRM and lead tracking to improve lead handling and marketing efficiency.',
            ],
            'tags' => ['SEO', 'Google Ads', 'Meta Ads', 'IndiaMART', 'Alibaba.com', 'CRM'],
        ],
        [
            'company' => 'Freelance',
            'role'    => 'Digital Marketing Executive',
            'period'  => '06/2026 — Present',
            'type'    => 'Freelance',
            'current' => true,
            'points'  => [
                'Independent SEO and digital marketing engagements for businesses alongside full-time work.',
            ],
            'tags' => ['SEO', 'Paid ads', 'Web'],
        ],
        [
            'company' => 'DG Knitting, Surat',
            'role'    => 'Digital Marketing Executive',
            'period'  => '02/2026 — 06/2026',
            'type'    => 'Full-time',
            'current' => false,
            'points'  => [
                'Developed SEO, paid ads and content strategies for brand expansion and lead generation.',
                'Optimised website content, product pages and blogs with on-page and technical SEO.',
                'Managed Google Ads & Meta Ads campaigns for lead generation and ROI.',
                'Built landing pages with strong CTAs in HTML, CSS, JavaScript and Bootstrap.',
                'Applied lead nurturing and CRM strategies for acquisition and retention.',
            ],
            'tags' => ['SEO', 'Google Ads', 'Meta Ads', 'Landing pages', 'CRM'],
        ],
        [
            'company' => 'Panchal Plastic Machinery Pvt. Ltd.',
            'role'    => 'Digital Marketing Specialist & SEO Expert',
            'period'  => '06/2023 — 11/2025',
            'type'    => 'Full-time',
            'current' => false,
            'points'  => [
                'Increased organic traffic by 200% through keyword research and on-page SEO.',
                'Improved rankings via site structure, internal linking and technical SEO.',
                'Redesigned the website layout with HTML, CSS, JavaScript and Bootstrap.',
                'Implemented lead generation with CTA landing pages and customer features.',
                'Used Google Maps API for mobile website deployment.',
                'Ran Google Ads and Meta Ads; analysed performance in Google Analytics and Meta Business Suite.',
            ],
            'tags' => ['SEO', 'Content', 'Web design', 'Google Ads', 'Meta Ads', 'Analytics'],
        ],
        [
            'company' => 'Shelvin Snacks Pvt. Ltd.',
            'role'    => 'Marketing Intern',
            'period'  => '01/2022 — 05/2023',
            'type'    => 'Internship',
            'current' => false,
            'points'  => [
                'Conducted market research and data analysis for marketing projects.',
                'Presented actionable recommendations to management.',
                'Contributed to strategic planning for product launches.',
            ],
            'tags' => ['Market research', 'Data analysis', 'Strategy'],
        ],
    ],

    // Skill index: [skill, category, where it was used, evidence]
    'skills' => [
        ['On-page & technical SEO',        'SEO',       'Arizone · DG Knitting · Panchal', '+200% organic traffic at Panchal'],
        ['Keyword research',               'SEO',       'Arizone · DG Knitting · Panchal', 'Core of every SEO engagement'],
        ['Competitor analysis',            'SEO',       'Arizone · DG Knitting',           'Ranking & visibility strategy'],
        ['Off-page SEO',                   'SEO',       'Freelance',                        'Authority building'],
        ['Website migration & index clean-up', 'SEO',   'Arizone',                          'WordPress → PHP, spam URLs removed'],
        ['Google Ads',                     'Paid',      'Arizone · DG Knitting · Panchal', 'Lead gen & ROI campaigns'],
        ['Meta Ads',                       'Paid',      'Arizone · DG Knitting · Panchal', 'Targeted brand & lead campaigns'],
        ['Social media marketing',         'Paid',      'Arizone · Panchal',               'Brand awareness campaigns'],
        ['IndiaMART lead management',      'B2B',       'Arizone',                          'Domestic B2B pipeline'],
        ['Alibaba.com Gold Supplier Plus', 'B2B',       'Arizone',                          'OEM/ODM listings, global leads'],
        ['TradeIndia',                     'B2B',       'B2B portfolio',                    'Marketplace presence'],
        ['CRM & lead tracking',            'B2B',       'Arizone · DG Knitting',           'Lead handling & nurturing'],
        ['Google Analytics',               'Analytics', 'Panchal',                          'Ad & traffic performance'],
        ['Meta Business Suite',            'Analytics', 'Panchal',                          'Campaign reporting'],
        ['Market research',                'Analytics', 'Shelvin Snacks',                   'Product launch planning'],
        ['HTML · CSS · JavaScript',        'Web',       'DG Knitting · Panchal',           'Landing pages & redesigns'],
        ['Bootstrap',                      'Web',       'DG Knitting · Panchal',           'Responsive layouts'],
        ['WordPress',                      'Web',       'Arizone',                          'CMS management & migration'],
        ['PHP',                            'Web',       'Arizone',                          'Custom company website'],
        ['Firebase Hosting · Google Maps API', 'Web',   'Panchal',                          'Mobile website deployment'],
        ['Content writing',                'Content',   'Panchal · DG Knitting',           'Blogs, product & web copy'],
    ],

    'process' => [
        ['Research', 'Keywords, competitors and buyer intent — before a single rupee is spent.'],
        ['Build',    'Fast, structured pages and listings that search engines and buyers both understand.'],
        ['Launch',   'SEO, Google Ads, Meta Ads and B2B portals working as one funnel.'],
        ['Measure',  'Analytics and CRM tracking to double down on what brings real leads.'],
    ],

    'education' => ['degree' => 'B.Sc. Information Technology', 'school' => 'University of Mumbai', 'period' => '2018 — 2021'],
    'soft'      => ['Communication', 'Research', 'Analytical thinking', 'Time management', 'Teamwork', 'Adaptability'],
];
