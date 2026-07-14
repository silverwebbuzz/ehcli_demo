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
 * Every tab / field / option also carries a `label_hi` (Hindi) alongside the
 * English `label`. Only the public intake page uses it (client-side language
 * toggle); the scorer and result view read `label` and ignore the extra key.
 *
 * NOTE: the auto score is a heuristic aid, NOT a diagnosis. The result view
 * always labels it "suggestion only".
 */

return [
    'miasms' => ['psora' => 'Psora', 'sycosis' => 'Sycosis', 'syphilis' => 'Syphilis', 'tubercular' => 'Tubercular'],

    'tabs' => [

        // ── 1. Personal ────────────────────────────────────────────────
        [
            'id' => 'personal', 'label' => 'Personal', 'label_hi' => 'व्यक्तिगत', 'icon' => 'fa-user',
            'fields' => [
                ['name' => 'occupation',  'label' => 'Occupation', 'label_hi' => 'व्यवसाय', 'type' => 'text'],
                ['name' => 'marital',     'label' => 'Marital status', 'label_hi' => 'वैवाहिक स्थिति', 'type' => 'select', 'options' => [
                    ['value' => 'single', 'label' => 'Single', 'label_hi' => 'अविवाहित'],
                    ['value' => 'married', 'label' => 'Married', 'label_hi' => 'विवाहित'],
                    ['value' => 'widowed', 'label' => 'Widowed', 'label_hi' => 'विधवा / विधुर'],
                    ['value' => 'divorced', 'label' => 'Divorced', 'label_hi' => 'तलाकशुदा'],
                ]],
                ['name' => 'diet',        'label' => 'Diet', 'label_hi' => 'आहार', 'type' => 'select', 'options' => [
                    ['value' => 'veg', 'label' => 'Vegetarian', 'label_hi' => 'शाकाहारी'],
                    ['value' => 'nonveg', 'label' => 'Non-vegetarian', 'label_hi' => 'मांसाहारी'],
                    ['value' => 'mixed', 'label' => 'Mixed', 'label_hi' => 'मिश्रित'],
                ]],
                ['name' => 'addictions',  'label' => 'Addictions (tea, coffee, tobacco, alcohol…)', 'label_hi' => 'व्यसन (चाय, कॉफी, तंबाकू, शराब…)', 'type' => 'text'],
            ],
        ],

        // ── 2. Chief Complaint & History ───────────────────────────────
        [
            'id' => 'complaint', 'label' => 'Complaint', 'label_hi' => 'शिकायत', 'icon' => 'fa-notes-medical',
            'fields' => [
                ['name' => 'chief_complaint', 'label' => 'Main complaint (in your own words)', 'label_hi' => 'मुख्य शिकायत (अपने शब्दों में)', 'type' => 'textarea', 'required' => true],
                ['name' => 'complaint_since', 'label' => 'Since how long?', 'label_hi' => 'कब से?', 'type' => 'text'],
                ['name' => 'complaint_worse', 'label' => 'What makes it worse?', 'label_hi' => 'किससे बढ़ती है?', 'type' => 'textarea'],
                ['name' => 'complaint_better','label' => 'What gives you relief?', 'label_hi' => 'किससे राहत मिलती है?', 'type' => 'textarea'],
                ['name' => 'onset',           'label' => 'How did the complaint start?', 'label_hi' => 'शिकायत कैसे शुरू हुई?', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'gradual',  'label' => 'Slowly / gradually', 'label_hi' => 'धीरे-धीरे',              'miasm' => ['psora' => 2]],
                    ['value' => 'recurrent','label' => 'Comes and goes / recurring', 'label_hi' => 'आती-जाती रहती है / बार-बार',      'miasm' => ['sycosis' => 2]],
                    ['value' => 'sudden',   'label' => 'Suddenly / after a shock', 'label_hi' => 'अचानक / किसी सदमे के बाद',        'miasm' => ['tubercular' => 2]],
                    ['value' => 'destructive','label' => 'Rapidly worsening / with damage', 'label_hi' => 'तेज़ी से बिगड़ती / क्षति के साथ','miasm' => ['syphilis' => 2]],
                ]],
            ],
        ],

        // ── 3. Mind & Emotions ─────────────────────────────────────────
        [
            'id' => 'mind', 'label' => 'Mind', 'label_hi' => 'मन', 'icon' => 'fa-brain',
            'fields' => [
                ['name' => 'temperament', 'label' => 'How would you describe your nature?', 'label_hi' => 'अपने स्वभाव का वर्णन कैसे करेंगे?', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'anxious',    'label' => 'Anxious, worried, insecure', 'label_hi' => 'चिंतित, परेशान, असुरक्षित',            'miasm' => ['psora' => 3]],
                    ['value' => 'ambitious',  'label' => 'Ambitious, restless, hurried', 'label_hi' => 'महत्वाकांक्षी, बेचैन, जल्दबाज़',          'miasm' => ['sycosis' => 2, 'tubercular' => 1]],
                    ['value' => 'irritable',  'label' => 'Irritable, destructive, discontent', 'label_hi' => 'चिड़चिड़ा, विध्वंसक, असंतुष्ट',    'miasm' => ['syphilis' => 3]],
                    ['value' => 'changeable', 'label' => 'Changeable, sensitive, needs variety', 'label_hi' => 'परिवर्तनशील, संवेदनशील, विविधता चाहने वाला',  'miasm' => ['tubercular' => 3]],
                ]],
                ['name' => 'fears',   'label' => 'Main fears / anxieties', 'label_hi' => 'मुख्य भय / चिंताएँ', 'type' => 'checkbox', 'score' => true, 'options' => [
                    ['value' => 'health',   'label' => 'About own health / poverty', 'label_hi' => 'अपने स्वास्थ्य / गरीबी के बारे में', 'miasm' => ['psora' => 2]],
                    ['value' => 'failure',  'label' => 'Of failure / being exposed', 'label_hi' => 'असफलता / उजागर होने का',  'miasm' => ['sycosis' => 2]],
                    ['value' => 'death',    'label' => 'Of death / disease / crowds', 'label_hi' => 'मृत्यु / रोग / भीड़ का', 'miasm' => ['syphilis' => 2]],
                    ['value' => 'confined', 'label' => 'Of being confined / suffocation', 'label_hi' => 'बंद जगह / घुटन का', 'miasm' => ['tubercular' => 2]],
                ]],
                ['name' => 'anger',   'label' => 'When upset, you tend to…', 'label_hi' => 'परेशान होने पर आप…', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'suppress',  'label' => 'Keep it inside / weep alone', 'label_hi' => 'मन में रखते हैं / अकेले रोते हैं',       'miasm' => ['psora' => 2]],
                    ['value' => 'conceal',   'label' => 'Hide it, stay diplomatic', 'label_hi' => 'छिपाते हैं, कूटनीतिक रहते हैं',          'miasm' => ['sycosis' => 2]],
                    ['value' => 'violent',   'label' => 'React strongly / break things', 'label_hi' => 'तीव्र प्रतिक्रिया / चीज़ें तोड़ते हैं',     'miasm' => ['syphilis' => 3]],
                    ['value' => 'cry',       'label' => 'Get emotional and moody quickly', 'label_hi' => 'जल्दी भावुक और मूडी हो जाते हैं',   'miasm' => ['tubercular' => 2]],
                ]],
                ['name' => 'mind_notes', 'label' => 'Anything else about your mind / stress', 'label_hi' => 'मन / तनाव के बारे में और कुछ', 'type' => 'textarea'],
            ],
        ],

        // ── 4. Physical Generals ───────────────────────────────────────
        [
            'id' => 'generals', 'label' => 'Generals', 'label_hi' => 'शारीरिक', 'icon' => 'fa-temperature-half',
            'fields' => [
                ['name' => 'thermal', 'label' => 'Your body & weather', 'label_hi' => 'आपका शरीर और मौसम', 'type' => 'radio', 'score' => true, 'required' => true, 'options' => [
                    ['value' => 'chilly', 'label' => 'I feel cold easily, prefer warmth', 'label_hi' => 'मुझे जल्दी ठंड लगती है, गर्मी पसंद है', 'thermal' => 'chilly', 'miasm' => ['psora' => 2]],
                    ['value' => 'hot',    'label' => 'I feel hot, prefer cool / open air', 'label_hi' => 'मुझे गर्मी लगती है, ठंडक / खुली हवा पसंद है', 'thermal' => 'hot',    'miasm' => ['tubercular' => 1, 'sycosis' => 1]],
                    ['value' => 'neutral','label' => 'Neither particularly', 'label_hi' => 'कोई खास नहीं',               'thermal' => 'neutral'],
                ]],
                ['name' => 'cravings', 'label' => 'Food cravings', 'label_hi' => 'भोजन की तीव्र इच्छा', 'type' => 'checkbox', 'score' => true, 'options' => [
                    ['value' => 'sweets', 'label' => 'Sweets', 'label_hi' => 'मीठा',           'miasm' => ['psora' => 1]],
                    ['value' => 'salt',   'label' => 'Salt / salty food', 'label_hi' => 'नमक / नमकीन', 'miasm' => ['tubercular' => 2]],
                    ['value' => 'sour',   'label' => 'Sour / pickles', 'label_hi' => 'खट्टा / अचार',    'miasm' => ['sycosis' => 1]],
                    ['value' => 'spicy',  'label' => 'Spicy / stimulating', 'label_hi' => 'तीखा / उत्तेजक','miasm' => ['sycosis' => 2]],
                    ['value' => 'fatty',  'label' => 'Fatty / fried', 'label_hi' => 'चिकना / तला हुआ',     'miasm' => ['tubercular' => 1]],
                ]],
                ['name' => 'aversions', 'label' => 'Food aversions', 'label_hi' => 'भोजन से अरुचि', 'type' => 'text'],
                ['name' => 'thirst',    'label' => 'Thirst', 'label_hi' => 'प्यास', 'type' => 'radio', 'options' => [
                    ['value' => 'much',    'label' => 'Very thirsty, large quantities', 'label_hi' => 'बहुत प्यास, अधिक मात्रा'],
                    ['value' => 'little',  'label' => 'Thirstless / little', 'label_hi' => 'कम प्यास / नहीं'],
                    ['value' => 'normal',  'label' => 'Normal', 'label_hi' => 'सामान्य'],
                ]],
                ['name' => 'perspiration', 'label' => 'Sweating', 'label_hi' => 'पसीना', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'profuse',    'label' => 'Profuse, gives relief', 'label_hi' => 'अधिक, राहत देता है',          'miasm' => ['psora' => 1]],
                    ['value' => 'offensive',  'label' => 'Profuse but offensive / staining', 'label_hi' => 'अधिक पर दुर्गंधयुक्त / दाग वाला','miasm' => ['sycosis' => 2]],
                    ['value' => 'scanty',     'label' => 'Very little / absent', 'label_hi' => 'बहुत कम / नहीं',            'miasm' => ['syphilis' => 2]],
                ]],
            ],
        ],

        // ── 5. Sleep & Dreams ──────────────────────────────────────────
        [
            'id' => 'sleep', 'label' => 'Sleep', 'label_hi' => 'नींद', 'icon' => 'fa-moon',
            'fields' => [
                ['name' => 'sleep_quality', 'label' => 'Sleep', 'label_hi' => 'नींद', 'type' => 'radio', 'score' => true, 'options' => [
                    ['value' => 'sound',      'label' => 'Sound and refreshing', 'label_hi' => 'गहरी और तरोताज़ा',              'miasm' => ['psora' => 1]],
                    ['value' => 'disturbed',  'label' => 'Light / disturbed / restless', 'label_hi' => 'हल्की / बाधित / बेचैन',      'miasm' => ['sycosis' => 1]],
                    ['value' => 'insomnia',   'label' => 'Difficulty falling asleep', 'label_hi' => 'नींद आने में कठिनाई',         'miasm' => ['tubercular' => 2]],
                ]],
                ['name' => 'sleep_position', 'label' => 'Preferred sleep position', 'label_hi' => 'सोने की पसंदीदा मुद्रा', 'type' => 'text'],
                ['name' => 'dreams',         'label' => 'Recurring dreams (if any)', 'label_hi' => 'बार-बार आने वाले सपने (यदि कोई हों)', 'type' => 'textarea'],
            ],
        ],

        // ── 6. Female / Menses (conditional) ───────────────────────────
        [
            'id' => 'female', 'label' => 'Female', 'label_hi' => 'स्त्री', 'icon' => 'fa-venus', 'when_gender' => 'female',
            'fields' => [
                ['name' => 'menses_cycle',  'label' => 'Menstrual cycle', 'label_hi' => 'मासिक चक्र', 'type' => 'select', 'options' => [
                    ['value' => 'regular', 'label' => 'Regular', 'label_hi' => 'नियमित'],
                    ['value' => 'irregular', 'label' => 'Irregular', 'label_hi' => 'अनियमित'],
                    ['value' => 'na', 'label' => 'Not applicable', 'label_hi' => 'लागू नहीं'],
                ]],
                ['name' => 'menses_flow',   'label' => 'Flow', 'label_hi' => 'रक्तस्राव', 'type' => 'select', 'options' => [
                    ['value' => 'scanty', 'label' => 'Scanty', 'label_hi' => 'कम'],
                    ['value' => 'moderate', 'label' => 'Moderate', 'label_hi' => 'मध्यम'],
                    ['value' => 'profuse', 'label' => 'Profuse', 'label_hi' => 'अधिक'],
                ]],
                ['name' => 'menses_notes',  'label' => 'Complaints during menses (pain, mood, etc.)', 'label_hi' => 'मासिक के दौरान शिकायतें (दर्द, मूड, आदि)', 'type' => 'textarea'],
            ],
        ],

        // ── 7. Past & Family History ───────────────────────────────────
        [
            'id' => 'history', 'label' => 'History', 'label_hi' => 'इतिहास', 'icon' => 'fa-clock-rotate-left',
            'fields' => [
                ['name' => 'past_illness',  'label' => 'Past major illnesses / surgeries', 'label_hi' => 'पूर्व गंभीर बीमारियाँ / ऑपरेशन', 'type' => 'textarea'],
                ['name' => 'family_history','label' => 'Family history (diabetes, BP, TB, cancer, asthma…)', 'label_hi' => 'पारिवारिक इतिहास (मधुमेह, बीपी, टीबी, कैंसर, दमा…)', 'type' => 'checkbox', 'score' => true, 'options' => [
                    ['value' => 'diabetes', 'label' => 'Diabetes / obesity', 'label_hi' => 'मधुमेह / मोटापा',       'miasm' => ['sycosis' => 2]],
                    ['value' => 'tb',       'label' => 'TB / asthma / allergies', 'label_hi' => 'टीबी / दमा / एलर्जी',  'miasm' => ['tubercular' => 3]],
                    ['value' => 'cancer',   'label' => 'Cancer / severe disease', 'label_hi' => 'कैंसर / गंभीर रोग',  'miasm' => ['syphilis' => 2]],
                    ['value' => 'skin',     'label' => 'Skin troubles / eczema', 'label_hi' => 'त्वचा रोग / एक्ज़िमा',   'miasm' => ['psora' => 2]],
                ]],
                ['name' => 'medications',   'label' => 'Current medications', 'label_hi' => 'वर्तमान दवाइयाँ', 'type' => 'textarea'],
            ],
        ],
    ],
];
