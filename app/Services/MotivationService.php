<?php

namespace App\Services;

class MotivationService
{
    /**
     * Inspiring quotes from Bharat's visionary thinkers, scientists, and scholars.
     */
    protected static array $quotes = [
        [
            'quote' => 'Arise, awake, and stop not until the goal is reached.',
            'author' => 'Swami Vivekananda',
            'slogan' => 'उत्तिष्ठत जाग्रत प्राप्य वरान्निबोधत',
            'meaning' => 'Arise, awake, and attain wisdom from the great ones.',
            'tag' => 'Focus & Perseverance',
            'category' => 'Mindset',
        ],
        [
            'quote' => 'Dream is not that which you see while sleeping, it is something that does not let you sleep.',
            'author' => 'Dr. A.P.J. Abdul Kalam',
            'slogan' => 'दृढ़संकल्पः सिद्धिं जनयति',
            'meaning' => 'Strong determination generates ultimate success.',
            'tag' => 'Vision & Dedication',
            'category' => 'Ambition',
        ],
        [
            'quote' => 'Education is the best friend. An educated person is respected everywhere. Knowledge beats youth and beauty.',
            'author' => 'Acharya Chanakya',
            'slogan' => 'विद्या मित्रं प्रवासेषु',
            'meaning' => 'Knowledge is a faithful friend across all journeys.',
            'tag' => 'Power of Knowledge',
            'category' => 'Wisdom',
        ],
        [
            'quote' => 'You cannot cross the sea merely by standing and staring at the water.',
            'author' => 'Rabindranath Tagore',
            'slogan' => 'कर्मण्येवाधिकारस्ते',
            'meaning' => 'Your right is in bold, dedicated action.',
            'tag' => 'Action & Initiative',
            'category' => 'Courage',
        ],
        [
            'quote' => 'Success can come to you by courageous devotion to the task lying in front of you.',
            'author' => 'Sir C.V. Raman (Nobel Laureate, Physics)',
            'slogan' => 'सत्यमेव जयते नानृतम्',
            'meaning' => 'Truth and scientific pursuit alone triumph.',
            'tag' => 'Courage & Science',
            'category' => 'Excellence',
        ],
        [
            'quote' => 'उत्तिष्ठत जाग्रत प्राप्य वरान्निबोधत — Arise, awake, and stop not till the goal is reached.',
            'author' => 'Swami Vivekananda (Katha Upanishad)',
            'slogan' => 'उत्तिष्ठत जाग्रत',
            'meaning' => 'Ignite your inner fire, shatter every limit, and conquer academic greatness.',
            'tag' => 'Unstoppable Ambition',
            'category' => 'Greatness',
        ],
        [
            'quote' => 'Never stop fighting until you arrive at your destined place — that is, the unique you.',
            'author' => 'Dr. A.P.J. Abdul Kalam',
            'slogan' => 'आत्मदीपो भव',
            'meaning' => 'Be your own light and craft your unique destiny.',
            'tag' => 'Individuality & Excellence',
            'category' => 'Identity',
        ],
        [
            'quote' => 'Take risks in your life. If you win, you can lead; if you lose, you can guide.',
            'author' => 'Swami Vivekananda',
            'slogan' => 'साहसे श्रीः वसति',
            'meaning' => 'Fortune and glory dwell in courageous perseverance.',
            'tag' => 'Leadership',
            'category' => 'Grit',
        ],
        [
            'quote' => 'Knowledge is the eternal light that dispels the darkness of ignorance and builds a glorious nation.',
            'author' => 'Maharshi Dayanand Saraswati',
            'slogan' => 'तमसो मा ज्योतिर्गमय',
            'meaning' => 'Lead us from the darkness of ignorance to eternal illumination.',
            'tag' => 'Nation Building',
            'category' => 'Wisdom',
        ],
        [
            'quote' => 'An equation for me has no meaning unless it expresses a thought of God.',
            'author' => 'Srinivasa Ramanujan (Mathematical Genius)',
            'slogan' => 'गणितं सर्वविद्यानां मूर्धनि वर्तते',
            'meaning' => 'Mathematics stands supreme as the crown of all sciences.',
            'tag' => 'Genius & Intuition',
            'category' => 'Mathematics',
        ],
        [
            'quote' => 'When you want to do something, all the world conspires in helping you achieve it, provided your dedication is absolute.',
            'author' => 'Prof. Satish Dhawan (Father of Experimental Fluid Dynamics)',
            'slogan' => 'न हि सिध्यन्ति कार्याणि मनोरथैः',
            'meaning' => 'Tasks are accomplished through relentless work, not wishful thinking.',
            'tag' => 'Dedication & Space',
            'category' => 'Engineering',
        ],
        [
            'quote' => 'Science is not a matter of intellectual luxury. It is a matter of life, existence, and sovereign dignity for our people.',
            'author' => 'Dr. Homi J. Bhabha',
            'slogan' => 'स्वावलम्बनं परं सुखम्',
            'meaning' => 'Self-reliance is the greatest source of strength and sovereignty.',
            'tag' => 'Visionary Self-Reliance',
            'category' => 'Science',
        ],
    ];

    /**
     * Mind-blowing scientific breakthroughs, historical achievements of Bharat, and cognitive learning facts.
     */
    protected static array $facts = [
        [
            'title' => 'Aryabhata & The Cosmic Measurement (499 CE)',
            'category' => 'Astronomy & Mathematics',
            'icon' => '🌌',
            'fact' => 'Over 1,500 years ago, Aryabhata calculated the Earth\'s circumference as 39,968 km — astonishingly just 0.2% off modern satellite measurements (40,075 km) — using pure trigonometry without telescopes or calculators.',
            'takeaway' => 'Pure mathematical logic can decode the universe from anywhere.',
            'fire_multiplier' => 'Level 5 Mind-Blower 🔥',
        ],
        [
            'title' => 'Srinivasa Ramanujan\'s 3,900 Breakthroughs',
            'category' => 'Mathematical Genius',
            'icon' => '📐',
            'fact' => 'Without formal university training in England, Ramanujan independently compiled nearly 3,900 identities, equations, and mock theta functions. Decades after his passing, his formulas are now used by astrophysicists to calculate the thermodynamic entropy of black holes.',
            'takeaway' => 'Your background does not define your intellectual ceiling. Relentless curiosity does.',
            'fire_multiplier' => 'Cosmic Insight ⚡',
        ],
        [
            'title' => 'Sir C.V. Raman & The ₹200 Nobel Experiment',
            'category' => 'Physics & Discovery',
            'icon' => '🔬',
            'fact' => 'Sir C.V. Raman discovered the Raman Effect (the inelastic scattering of photons) using an improvised laboratory setup costing less than ₹200 at the Indian Association for the Cultivation of Science in Kolkata, earning the 1930 Nobel Prize in Physics.',
            'takeaway' => 'Great discoveries do not depend on lavish apparatus; they depend on relentless observation.',
            'fire_multiplier' => 'Nobel Laureate Fire 🔥',
        ],
        [
            'title' => 'The 25-Minute Neuroplasticity Sweet Spot',
            'category' => 'Cognitive Neuroscience',
            'icon' => '🧠',
            'fact' => 'Neuroscience proves that engaging in uninterrupted deep practice for 25–40 minutes releases brain-derived neurotrophic factor (BDNF). This protein physically thickens the myelin sheath around active neurons, accelerating problem-solving speed by up to 300%.',
            'takeaway' => 'One focused 30-minute DPP session rewires your brain faster than 3 hours of distracted browsing.',
            'fire_multiplier' => 'Neural Superpower ⚡',
        ],
        [
            'title' => 'The Law of Compound Intellectual Growth',
            'category' => 'Habit Dynamics',
            'icon' => '📈',
            'fact' => 'If you improve your conceptual mastery by just 1% each day, mathematical compounding dictates: 1.01³⁶⁵ = 37.78. By year\'s end, you will be nearly 38 times more capable. Conversely, 0.99³⁶⁵ = 0.03 (decaying to almost zero).',
            'takeaway' => 'Small daily consistency triumphs over erratic bursts of cramming every single time.',
            'fire_multiplier' => 'Compound Engine 🚀',
        ],
        [
            'title' => 'J.C. Bose & The Birth of Wireless Waves (1895)',
            'category' => 'Invention & Integrity',
            'icon' => '📡',
            'fact' => 'In 1895, Sir Jagadish Chandra Bose demonstrated millimeter-length radio waves and invented the horn antenna and crystal detector. He deliberately refused to patent his work, believing knowledge belongs freely to all humanity.',
            'takeaway' => 'Knowledge multiplies when ignited for the welfare of society.',
            'fire_multiplier' => 'National Heritage 🇮🇳',
        ],
        [
            'title' => 'Brahmagupta & The Mathematical Zero (628 CE)',
            'category' => 'Algebra & Computation',
            'icon' => '🔢',
            'fact' => 'In his treatise Brāhmasphuṭasiddhānta (628 CE), Brahmagupta was the first scholar in human history to establish formal arithmetic rules for zero (0) and negative numbers, laying the foundation for modern algorithmic computing.',
            'takeaway' => 'From zero to infinity, Indian scholarship shaped the architecture of modern technology.',
            'fire_multiplier' => 'Foundation of Code ⚡',
        ],
        [
            'title' => 'The Chandrasekhar Limit at Age 19',
            'category' => 'Astrophysics',
            'icon' => '⭐',
            'fact' => 'Subrahmanyan Chandrasekhar formulated the Chandrasekhar Limit (1.44 solar masses, determining whether dying stars become white dwarfs or collapse into neutron stars) at the age of 19 while traveling on a steamship from India to England.',
            'takeaway' => 'Youth is no barrier to profound scientific breakthrough.',
            'fire_multiplier' => 'Cosmic Genius 🌟',
        ],
        [
            'title' => 'Bloom\'s Level 6 Brain Activation',
            'category' => 'Cognitive Psychology',
            'icon' => '🎯',
            'fact' => 'fMRI neuroimaging reveals that answering multiple-choice recall questions (Level 1) activates less than 15% of the cerebral cortex. Designing an experiment or formulating a hypothesis (Level 5–6) sparks over 65% bidirectional cortical firing.',
            'takeaway' => 'Do not just memorize formulas; construct experiments and solve original problems!',
            'fire_multiplier' => 'Cognitive Spark 🔥',
        ],
        [
            'title' => 'Kalam\'s 100 Failed Rocket Launches Before SLV-3',
            'category' => 'Resilience & Grit',
            'icon' => '🚀',
            'fact' => 'When India\'s first satellite launch vehicle (SLV-3) crashed into the Bay of Bengal in 1979, project director A.P.J. Abdul Kalam worked 18-hour days with his team, analyzed telemetry, and succeeded the very next year in placing Rohini into orbit in 1980.',
            'takeaway' => 'A failed test is simply diagnostic data guiding your next victory.',
            'fire_multiplier' => 'Unstoppable Grit 💪',
        ],
    ];

    /**
     * Compute Fire Heat Level and Motivational Badge from streak count.
     */
    public static function getFireStatus(int $streakCount): array
    {
        $streak = max(1, $streakCount);

        if ($streak >= 30) {
            return [
                'level' => 5,
                'title' => 'Eternal Agni Master',
                'badge' => '👑🔥',
                'label' => 'God-Tier Momentum',
                'description' => '30+ Days of indomitable daily discipline! Your intellect is an unstoppable beacon.',
                'color' => 'from-rose-600 via-orange-500 to-amber-400',
                'bg_glow' => 'rgba(239, 68, 68, 0.25)',
                'next_milestone' => 50,
                'days_to_next' => max(0, 50 - $streak),
                'intensity_pct' => 100,
            ];
        }

        if ($streak >= 14) {
            return [
                'level' => 4,
                'title' => 'Cosmic Supernova',
                'badge' => '🌟🔥',
                'label' => 'Elite Habit Formed',
                'description' => 'Two unbroken weeks of scholarly excellence! Synaptic pathways are permanently reinforced.',
                'color' => 'from-purple-600 via-pink-600 to-orange-500',
                'bg_glow' => 'rgba(217, 70, 239, 0.25)',
                'next_milestone' => 30,
                'days_to_next' => 30 - $streak,
                'intensity_pct' => min(100, 75 + ($streak - 14) * 1.5),
            ];
        }

        if ($streak >= 7) {
            return [
                'level' => 3,
                'title' => 'Raging Inferno',
                'badge' => '⚡🔥',
                'label' => 'One Week Conquered',
                'description' => '7+ Consecutive days of academic fire! You have broken past the resistance barrier.',
                'color' => 'from-orange-500 via-amber-500 to-yellow-400',
                'bg_glow' => 'rgba(249, 115, 22, 0.25)',
                'next_milestone' => 14,
                'days_to_next' => 14 - $streak,
                'intensity_pct' => min(100, 50 + ($streak - 7) * 3.5),
            ];
        }

        if ($streak >= 3) {
            return [
                'level' => 2,
                'title' => 'Blazing Momentum',
                'badge' => '🔥🔥',
                'label' => 'Thermal Burn Active',
                'description' => '3+ Consecutive days in the zone! Momentum is building and habits are locking in.',
                'color' => 'from-amber-500 via-orange-400 to-rose-500',
                'bg_glow' => 'rgba(245, 158, 11, 0.22)',
                'next_milestone' => 7,
                'days_to_next' => 7 - $streak,
                'intensity_pct' => min(100, 30 + ($streak - 3) * 5),
            ];
        }

        return [
            'level' => 1,
            'title' => 'Kindle Spark',
            'badge' => '🔥',
            'label' => 'Ignition Initiated',
            'description' => 'The journey of a thousand milestones begins with today\'s single focused spark!',
            'color' => 'from-emerald-500 via-teal-400 to-cyan-500',
            'bg_glow' => 'rgba(16, 185, 129, 0.20)',
            'next_milestone' => 3,
            'days_to_next' => 3 - $streak,
            'intensity_pct' => 20,
        ];
    }

    public static function getRandomQuote(): array
    {
        return self::$quotes[array_rand(self::$quotes)];
    }

    public static function getDailyQuote(): array
    {
        $dayOfYear = (int) date('z');

        return self::$quotes[$dayOfYear % count(self::$quotes)];
    }

    public static function getRandomFact(): array
    {
        return self::$facts[array_rand(self::$facts)];
    }

    public static function getDailyFact(): array
    {
        $dayOfYear = (int) date('z');

        return self::$facts[$dayOfYear % count(self::$facts)];
    }

    /**
     * Complete payload for motivational UI widget.
     */
    public static function getSparkPayload(?int $streak = 1): array
    {
        $quote = self::getRandomQuote();
        $fact = self::getRandomFact();
        $fire = self::getFireStatus($streak ?? 1);

        return [
            'quote' => $quote,
            'fact' => $fact,
            'fire' => $fire,
            'slogan' => 'Aim, Ascend, Achieve',
            'timestamp' => now()->toISOString(),
        ];
    }
}
