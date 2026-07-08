<?php
/**
 * Homeopathy Intake Questionnaire — shared schema.
 *
 * Single source of truth for:
 *   - the tabbed form UI (staff + public),
 *   - server-side validation of a submission,
 *   - the auto miasm / thermal scorer,
 *   - the doctor's case-sheet result view.
 *
 * Scored options carry a `miasm` weight map (psora/sycosis/syphilis/tubercular)
 * and/or a `thermal` tag (hot/chilly). Free-text fields are stored verbatim and
 * shown on the case sheet but never scored.
 *
 * NOTE: the auto score is a heuristic aid, NOT a diagnosis. The result view
 * always labels it "suggestion only".
 */

return [
    'miasms' => ['psora' => 'Psora', 'sycosis' => 'Sycosis', 'syphilis' => 'Syphilis', 'tubercular' => 'Tubercular'],

    'tabs' => [

        // ── 1. Personal ────────────────────────────────────────────────
        [
            'id' => 'personal', 'label' => 'Personal', 'icon' => 'fa-user',
            'fields' => [
                ['name' => 'occupation',  'label' => 'Occupation',                    'type' => 'text'],
                ['name' => 'marital',     'label' => 'Marital status',                 'type' => 'select', 'options' => [
                    ['value' => 'single', 'label' => 'Single'],
                    ['value' => 'married', 'label' => 'Married'],
                    ['value' => 'widowed', 'label' => 'Widowed'],
                    ['value' => 'divorced', 'label' => 'Divorced'],
                ]],
                ['name' => 'diet',        'label' => 'Diet',                           'type' => 'select', 'options' => [
                    ['value' => 'veg', 'label' => 'Vegetarian'],
                    ['value' => 'nonveg', 'label' => 'Non-vegetarian'],
                    ['value' => 'mixed', 'label' => 'Mixed'],
                ]],
                ['name' => 'addictions',  'label' => 'Addictions (tea, coffee, tobacco, alcohol…)', 'type' => 'text'],
            ],
        ],

        // ── 2. Chief Complaint & History ───────────────────────────────
        [
            'id' => 'complaint', 'label' => 'Complaint', 'icon' => 'fa-notes-medical',
            'fields' => [
                ['name' => 'chief_complaint', 'label' => 'Main complaint (in your own words)', 'type' => 'textarea', 'required' => true],
                ['name' => 'complaint_since', 'label' => 'Since how long?',                    'type' => 'text'],
                ['name' => 'complaint_worse', 'label' => 'What makes it worse?',               'type' => 'textarea'],
                ['name' => 'complaint_better','label' => 'What gives you relief?',             'type' => 'textarea'],
                ['name' => 'onset',           'label' => 'How did the complaint start?',       'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'gradual',  'label' => 'Slowly / gradually',              'miasm' => ['psora' => 2]],
                    ['value' => 'recurrent','label' => 'Comes and goes / recurring',      'miasm' => ['sycosis' => 2]],
                    ['value' => 'sudden',   'label' => 'Suddenly / after a shock',        'miasm' => ['tubercular' => 2]],
                    ['value' => 'destructive','label' => 'Rapidly worsening / with damage','miasm' => ['syphilis' => 2]],
                ]],
            ],
        ],

        // ── 3. Mind & Emotions ─────────────────────────────────────────
        [
            'id' => 'mind', 'label' => 'Mind', 'icon' => 'fa-brain',
            'fields' => [
                ['name' => 'temperament', 'label' => 'How would you describe your nature?', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'anxious',    'label' => 'Anxious, worried, insecure',            'miasm' => ['psora' => 3]],
                    ['value' => 'ambitious',  'label' => 'Ambitious, restless, hurried',          'miasm' => ['sycosis' => 2, 'tubercular' => 1]],
                    ['value' => 'irritable',  'label' => 'Irritable, destructive, discontent',    'miasm' => ['syphilis' => 3]],
                    ['value' => 'changeable', 'label' => 'Changeable, sensitive, needs variety',  'miasm' => ['tubercular' => 3]],
                ]],
                ['name' => 'fears',   'label' => 'Main fears / anxieties',       'type' => 'checkbox', 'score' => true, 'options' => [
                    ['value' => 'health',   'label' => 'About own health / poverty', 'miasm' => ['psora' => 2]],
                    ['value' => 'failure',  'label' => 'Of failure / being exposed',  'miasm' => ['sycosis' => 2]],
                    ['value' => 'death',    'label' => 'Of death / disease / crowds', 'miasm' => ['syphilis' => 2]],
                    ['value' => 'confined', 'label' => 'Of being confined / suffocation', 'miasm' => ['tubercular' => 2]],
                ]],
                ['name' => 'anger',   'label' => 'When upset, you tend to…',     'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'suppress',  'label' => 'Keep it inside / weep alone',       'miasm' => ['psora' => 2]],
                    ['value' => 'conceal',   'label' => 'Hide it, stay diplomatic',          'miasm' => ['sycosis' => 2]],
                    ['value' => 'violent',   'label' => 'React strongly / break things',     'miasm' => ['syphilis' => 3]],
                    ['value' => 'cry',       'label' => 'Get emotional and moody quickly',   'miasm' => ['tubercular' => 2]],
                ]],
                ['name' => 'mind_notes', 'label' => 'Anything else about your mind / stress', 'type' => 'textarea'],
            ],
        ],

        // ── 4. Physical Generals ───────────────────────────────────────
        [
            'id' => 'generals', 'label' => 'Generals', 'icon' => 'fa-temperature-half',
            'fields' => [
                ['name' => 'thermal', 'label' => 'Your body & weather', 'type' => 'radio', 'score' => true, 'required' => true, 'options' => [
                    ['value' => 'chilly', 'label' => 'I feel cold easily, prefer warmth', 'thermal' => 'chilly', 'miasm' => ['psora' => 2]],
                    ['value' => 'hot',    'label' => 'I feel hot, prefer cool / open air', 'thermal' => 'hot',    'miasm' => ['tubercular' => 1, 'sycosis' => 1]],
                    ['value' => 'neutral','label' => 'Neither particularly',               'thermal' => 'neutral'],
                ]],
                ['name' => 'cravings', 'label' => 'Food cravings', 'type' => 'checkbox', 'score' => true, 'options' => [
                    ['value' => 'sweets', 'label' => 'Sweets',           'miasm' => ['psora' => 1]],
                    ['value' => 'salt',   'label' => 'Salt / salty food', 'miasm' => ['tubercular' => 2]],
                    ['value' => 'sour',   'label' => 'Sour / pickles',    'miasm' => ['sycosis' => 1]],
                    ['value' => 'spicy',  'label' => 'Spicy / stimulating','miasm' => ['sycosis' => 2]],
                    ['value' => 'fatty',  'label' => 'Fatty / fried',     'miasm' => ['tubercular' => 1]],
                ]],
                ['name' => 'aversions', 'label' => 'Food aversions', 'type' => 'text'],
                ['name' => 'thirst',    'label' => 'Thirst', 'type' => 'radio', 'options' => [
                    ['value' => 'much',    'label' => 'Very thirsty, large quantities'],
                    ['value' => 'little',  'label' => 'Thirstless / little'],
                    ['value' => 'normal',  'label' => 'Normal'],
                ]],
                ['name' => 'perspiration', 'label' => 'Sweating', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'profuse',    'label' => 'Profuse, gives relief',          'miasm' => ['psora' => 1]],
                    ['value' => 'offensive',  'label' => 'Profuse but offensive / staining','miasm' => ['sycosis' => 2]],
                    ['value' => 'scanty',     'label' => 'Very little / absent',            'miasm' => ['syphilis' => 2]],
                ]],
            ],
        ],

        // ── 5. Sleep & Dreams ──────────────────────────────────────────
        [
            'id' => 'sleep', 'label' => 'Sleep', 'icon' => 'fa-moon',
            'fields' => [
                ['name' => 'sleep_quality', 'label' => 'Sleep', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'sound',      'label' => 'Sound and refreshing',              'miasm' => ['psora' => 1]],
                    ['value' => 'disturbed',  'label' => 'Light / disturbed / restless',      'miasm' => ['sycosis' => 1]],
                    ['value' => 'insomnia',   'label' => 'Difficulty falling asleep',         'miasm' => ['tubercular' => 2]],
                ]],
                ['name' => 'sleep_position', 'label' => 'Preferred sleep position', 'type' => 'text'],
                ['name' => 'dreams',         'label' => 'Recurring dreams (if any)', 'type' => 'textarea'],
            ],
        ],

        // ── 6. Female / Menses (conditional) ───────────────────────────
        [
            'id' => 'female', 'label' => 'Female', 'icon' => 'fa-venus', 'when_gender' => 'female',
            'fields' => [
                ['name' => 'menses_cycle',  'label' => 'Menstrual cycle', 'type' => 'select', 'options' => [
                    ['value' => 'regular', 'label' => 'Regular'],
                    ['value' => 'irregular', 'label' => 'Irregular'],
                    ['value' => 'na', 'label' => 'Not applicable'],
                ]],
                ['name' => 'menses_flow',   'label' => 'Flow', 'type' => 'select', 'options' => [
                    ['value' => 'scanty', 'label' => 'Scanty'],
                    ['value' => 'moderate', 'label' => 'Moderate'],
                    ['value' => 'profuse', 'label' => 'Profuse'],
                ]],
                ['name' => 'menses_notes',  'label' => 'Complaints during menses (pain, mood, etc.)', 'type' => 'textarea'],
            ],
        ],

        // ── 7. Past & Family History ───────────────────────────────────
        [
            'id' => 'history', 'label' => 'History', 'icon' => 'fa-clock-rotate-left',
            'fields' => [
                ['name' => 'past_illness',  'label' => 'Past major illnesses / surgeries', 'type' => 'textarea'],
                ['name' => 'family_history','label' => 'Family history (diabetes, BP, TB, cancer, asthma…)', 'type' => 'checkbox', 'score' => true, 'options' => [
                    ['value' => 'diabetes', 'label' => 'Diabetes / obesity',       'miasm' => ['sycosis' => 2]],
                    ['value' => 'tb',       'label' => 'TB / asthma / allergies',  'miasm' => ['tubercular' => 3]],
                    ['value' => 'cancer',   'label' => 'Cancer / severe disease',  'miasm' => ['syphilis' => 2]],
                    ['value' => 'skin',     'label' => 'Skin troubles / eczema',   'miasm' => ['psora' => 2]],
                ]],
                ['name' => 'medications',   'label' => 'Current medications', 'type' => 'textarea'],
            ],
        ],
    ],
];
