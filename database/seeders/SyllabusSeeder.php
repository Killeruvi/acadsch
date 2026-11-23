<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\TopicBloomActivity;
use Illuminate\Database\Seeder;

class SyllabusSeeder extends Seeder
{
    /**
     * Run the comprehensive syllabus curriculum seeder for Classes 1 to 12.
     */
    public function run(): void
    {
        $curriculum = $this->getCurriculumData();

        foreach ($curriculum as $gradeLevel => $classData) {
            $class = AcademicClass::firstOrCreate(
                ['grade_level' => $gradeLevel],
                [
                    'name' => "Class {$gradeLevel}",
                    'section' => 'A',
                    'academic_year' => '2026-2027',
                ]
            );

            foreach ($classData['subjects'] as $subjData) {
                $codeKey = strtoupper(trim((string) $subjData['code']));
                $subject = AcademicSubject::where('class_id', $class->id)
                    ->where(function ($q) use ($subjData, $codeKey) {
                        $q->where('name', $subjData['name'])
                          ->orWhere('code_key', $codeKey)
                          ->orWhere('code', $codeKey);
                    })
                    ->first();
                if (!$subject) {
                    $subject = AcademicSubject::create([
                        'class_id' => $class->id,
                        'name' => $subjData['name'],
                        'code' => $subjData['code'],
                    ]);
                }

                foreach ($subjData['chapters'] as $ch) {
                    $topic = CurriculumTopic::updateOrCreate(
                        [
                            'subject_id' => $subject->id,
                            'chapter_number' => $ch['number'],
                        ],
                        [
                            'title' => $ch['title'],
                            'description' => $ch['description'],
                            'subtopics' => $ch['subtopics'],
                        ]
                    );

                    // Ensure all 6 Bloom levels are mapped for every chapter!
                    $this->seedBloomActivities($topic);
                }
            }
        }

        $this->seedExtendedCurriculum();
    }

    /**
     * Ensure complete NCERT chapters across all Class 1-12 subjects are fully populated.
     */
    protected function seedExtendedCurriculum(): void
    {
        $additions = [
            // Class 7 Science
            ['class' => 7, 'subject' => 'Science', 'chapters' => [
                ['number' => 9, 'title' => 'Motion and Time', 'description' => 'Speed measurement, distance-time graphs, and simple pendulums.', 'subtopics' => ['Slow or Fast Motion', 'Speed Calculation', 'Measurement of Time', 'Distance-Time Graph']],
                ['number' => 10, 'title' => 'Electric Current and its Effects', 'description' => 'Symbols of electric components, heating and magnetic effects, electromagnets.', 'subtopics' => ['Symbols of Components', 'Heating Effect & Fuses', 'Magnetic Effect & Electromagnets', 'Electric Bell Working']],
                ['number' => 11, 'title' => 'Light', 'description' => 'Rectilinear propagation, reflection of light, plane mirrors, spherical mirrors, and lenses.', 'subtopics' => ['Light Along a Straight Line', 'Reflection & Plane Mirrors', 'Concave & Convex Mirrors', 'Images Formed by Lenses', 'Sunlight & Rainbow Dispersion']],
                ['number' => 12, 'title' => 'Forests: Our Lifeline', 'description' => 'Forest profile, canopy, decomposers, and ecological balance in nature.', 'subtopics' => ['Forest as Dynamic Living Entity', 'Interdependence of Plants & Animals', 'Crown and Understorey', 'Forest Conservation']],
                ['number' => 13, 'title' => 'Wastewater Story', 'description' => 'Sewage composition, wastewater treatment plants (WWTP), and sanitation hygiene.', 'subtopics' => ['Sewage Origin & Composition', 'Wastewater Treatment Plant Stages', 'Better Housekeeping Practices', 'Sanitation and Disease']],
            ]],

            // Class 7 Mathematics
            ['class' => 7, 'subject' => 'Mathematics', 'chapters' => [
                ['number' => 9, 'title' => 'Practical Geometry', 'description' => 'Construction of parallel lines and triangles with given criteria (SSS, SAS, ASA, RHS).', 'subtopics' => ['Construction of Parallel Lines', 'SSS Triangle Construction', 'SAS Triangle Construction', 'ASA & RHS Triangle Construction']],
                ['number' => 10, 'title' => 'Perimeter and Area', 'description' => 'Perimeter and area of squares, rectangles, parallelograms, triangles, and circles.', 'subtopics' => ['Squares and Rectangles', 'Area of a Parallelogram', 'Area of a Triangle', 'Circumference & Area of Circles', 'Applications in Paths & Borders']],
                ['number' => 11, 'title' => 'Algebraic Expressions', 'description' => 'Terms, coefficients, like and unlike terms, addition, subtraction, and finding values.', 'subtopics' => ['How Expressions are Formed', 'Terms of an Expression', 'Like and Unlike Terms', 'Addition and Subtraction of Algebraic Expressions', 'Finding Value of an Expression']],
                ['number' => 12, 'title' => 'Exponents and Powers', 'description' => 'Exponential notation, laws of exponents, and standard scientific form of numbers.', 'subtopics' => ['Exponential Form Basics', 'Laws of Exponents (Multiplication & Division)', 'Power of a Power', 'Numbers in Standard Scientific Form']],
                ['number' => 13, 'title' => 'Symmetry', 'description' => 'Line symmetry, rotational symmetry, order of rotational symmetry in 2D shapes.', 'subtopics' => ['Lines of Symmetry for Regular Polygons', 'Rotational Symmetry Basics', 'Center of Rotation & Angle', 'Line Symmetry and Rotational Symmetry']],
            ]],

            // Class 7 Social Science
            ['class' => 7, 'subject' => 'Social Science', 'chapters' => [
                ['number' => 9, 'title' => 'Markets Around Us', 'description' => 'Weekly markets, neighbourhood shops, shopping complexes, and chain of markets.', 'subtopics' => ['Weekly Markets', 'Shops in the Neighbourhood', 'Shopping Complexes & Malls', 'Chain of Markets & Equality']],
                ['number' => 10, 'title' => 'Struggles for Equality', 'description' => 'Indian Constitution as a living document, democratic movements, and Tawa Matsya Sangh.', 'subtopics' => ['The Indian Constitution as Living Document', 'Inequalities in Society', 'Tawa Matsya Sangh Case Study', 'Living the Struggle for Equality']],
            ]],

            // Class 11 Biology
            ['class' => 11, 'subject' => 'Biology', 'chapters' => [
                ['number' => 7, 'title' => 'Structural Organisation in Animals', 'description' => 'Animal tissues: epithelial, connective, muscular, neural, and organ systems.', 'subtopics' => ['Epithelial Tissue Types', 'Connective Tissues', 'Muscle & Neural Tissue', 'Cockroach/Frog Organ Systems']],
                ['number' => 8, 'title' => 'Cell: The Unit of Life', 'description' => 'Prokaryotic and eukaryotic cells, cell membrane, endomembrane system, and organelles.', 'subtopics' => ['What is a Cell', 'Cell Theory', 'Fluid Mosaic Membrane Model', 'Endomembrane System', 'Mitochondria, Chloroplasts & Nucleus']],
                ['number' => 9, 'title' => 'Biomolecules', 'description' => 'Primary and secondary metabolites, carbohydrates, proteins, nucleic acids, and enzymes.', 'subtopics' => ['Chemical Analysis of Biomolecules', 'Amino Acids and Proteins Structure', 'Nucleic Acids (DNA/RNA)', 'Enzyme Classification & Kinetics']],
                ['number' => 10, 'title' => 'Cell Cycle and Cell Division', 'description' => 'Phases of cell cycle, stages of mitosis, significance of meiosis, and genetic variation.', 'subtopics' => ['Cell Cycle (Interphase)', 'Mitosis Stages (Prophase to Telophase)', 'Meiosis I & Meiosis II', 'Significance of Cell Division']],
                ['number' => 11, 'title' => 'Photosynthesis in Higher Plants', 'description' => 'Light reactions, electron transport, chemiosmotic hypothesis, C3 and C4 pathways.', 'subtopics' => ['Early Experiments & Pigments', 'Light Reaction & Photophosphorylation', 'Chemiosmotic Hypothesis', 'Calvin Cycle & C4 Pathway', 'Factors Affecting Photosynthesis']],
                ['number' => 12, 'title' => 'Respiration in Plants', 'description' => 'Glycolysis, fermentation, Krebs cycle, electron transport system, and respiratory quotient.', 'subtopics' => ['Do Plants Breathe', 'Glycolytic Pathway', 'TCA Cycle (Krebs Cycle)', 'Electron Transport System (ETS)', 'Respiratory Quotient (RQ)']],
                ['number' => 13, 'title' => 'Plant Growth and Development', 'description' => 'Growth characteristics, differentiation, development, and plant growth regulators.', 'subtopics' => ['Phases of Growth', 'Differentiation & Dedifferentiation', 'Auxins, Gibberellins, Cytokinins', 'Abscisic Acid & Ethylene', 'Photoperiodism & Vernalisation']],
                ['number' => 14, 'title' => 'Breathing and Exchange of Gases', 'description' => 'Respiratory organs, mechanism of breathing, gas exchange, and respiratory regulation.', 'subtopics' => ['Human Respiratory System', 'Mechanism of Breathing & Volumes', 'Gas Exchange in Alveoli', 'Transport of O2 and CO2', 'Respiratory Disorders']],
                ['number' => 15, 'title' => 'Body Fluids and Circulation', 'description' => 'Blood and lymph, human circulatory system, cardiac cycle, ECG, and blood pressure.', 'subtopics' => ['Blood Composition & Groups', 'Lymph (Tissue Fluid)', 'Human Circulatory System & Heart', 'Cardiac Cycle & ECG', 'Double Circulation & Disorders']],
                ['number' => 16, 'title' => 'Excretory Products and their Elimination', 'description' => 'Human excretory system, urine formation, regulation of kidney function, and micturition.', 'subtopics' => ['Ammonotelism, Ureotelism, Uricotelism', 'Nephron Structure', 'Urine Formation Mechanism', 'Countercurrent Mechanism', 'RAAS Regulation & Dialysis']],
                ['number' => 17, 'title' => 'Locomotion and Movement', 'description' => 'Types of movement, muscle contraction sliding filament theory, skeletal joints.', 'subtopics' => ['Types of Movement', 'Structure of Contractile Proteins', 'Sliding Filament Mechanism', 'Human Skeletal System', 'Joints and Disorders']],
                ['number' => 18, 'title' => 'Neural Control and Coordination', 'description' => 'Neural system, generation and conduction of nerve impulse, reflex arc, and sensory organs.', 'subtopics' => ['Neurons as Structural Units', 'Conduction of Nerve Impulses', 'Synaptic Transmission', 'Human Brain Anatomy', 'Reflex Action & Sensory Organs']],
                ['number' => 19, 'title' => 'Chemical Coordination and Integration', 'description' => 'Endocrine glands, hormones, mechanism of hormone action, and feedback loops.', 'subtopics' => ['Hypothalamus and Pituitary Gland', 'Thyroid, Parathyroid, Adrenal Glands', 'Pancreas & Gonadal Hormones', 'Mechanism of Hormone Action']],
            ]],

            // Class 12 Biology
            ['class' => 12, 'subject' => 'Biology', 'chapters' => [
                ['number' => 7, 'title' => 'Human Health and Disease', 'description' => 'Common infectious diseases, innate and acquired immunity, AIDS, cancer, and drug abuse.', 'subtopics' => ['Pathogens & Communicable Diseases', 'Innate & Acquired Immunity', 'Antigen-Antibody Interaction', 'AIDS & HIV Cycle', 'Cancer Biology', 'Drugs and Alcohol Abuse']],
                ['number' => 8, 'title' => 'Microbes in Human Welfare', 'description' => 'Household and industrial applications of microbes, sewage treatment, biogas, and biocontrol.', 'subtopics' => ['Microbes in Household Food', 'Industrial Products (Antibiotics & Enzymes)', 'Microbes in Sewage Treatment', 'Biogas Production', 'Microbes as Biocontrol Agents & Biofertilisers']],
                ['number' => 9, 'title' => 'Biotechnology: Principles and Processes', 'description' => 'Recombinant DNA technology, restriction enzymes, cloning vectors, and PCR.', 'subtopics' => ['Principles of Biotechnology', 'Tools of Recombinant DNA Technology', 'Restriction Endonucleases', 'Cloning Vectors (pBR322)', 'Polymerase Chain Reaction (PCR)', 'Bioreactors & Downstream Processing']],
                ['number' => 10, 'title' => 'Biotechnology and its Applications', 'description' => 'Applications in agriculture, medicine, gene therapy, transgenic animals, and biosafety.', 'subtopics' => ['Bt Cotton & Pest Resistant Plants', 'Genetically Engineered Insulin', 'Gene Therapy (ADA Deficiency)', 'Transgenic Animals', 'Biopiracy and Ethical Issues']],
                ['number' => 11, 'title' => 'Organisms and Populations', 'description' => 'Organism and its environment, major abiotic factors, adaptations, population growth models.', 'subtopics' => ['Abiotic Factors (Temperature, Water, Light)', 'Responses to Abiotic Factors', 'Ecological Adaptations', 'Population Attributes & Age Pyramids', 'Exponential & Logistic Growth', 'Population Interactions (Mutualism, Parasitism)']],
                ['number' => 12, 'title' => 'Ecosystem', 'description' => 'Ecosystem structure, productivity, decomposition, energy flow, and ecological pyramids.', 'subtopics' => ['Ecosystem Components', 'Productivity (GPP & NPP)', 'Decomposition Process', 'Energy Flow & 10% Law', 'Ecological Pyramids (Number, Biomass, Energy)', 'Ecological Succession']],
                ['number' => 13, 'title' => 'Biodiversity and Conservation', 'description' => 'Levels of biodiversity, species patterns, loss of biodiversity, and in-situ/ex-situ conservation.', 'subtopics' => ['Genetic, Species & Ecological Diversity', 'Latitudinal Gradients & Species-Area Curve', 'The Evil Quartet (Causes of Biodiversity Loss)', 'In-situ & Ex-situ Conservation', 'Hotspots & Protected Areas']],
            ]],

            // Class 11 Computer Science
            ['class' => 11, 'subject' => 'Computer Science', 'chapters' => [
                ['number' => 7, 'title' => 'Lists in Python', 'description' => 'List creation, traversal, indexing, slicing, built-in list methods, and nested lists.', 'subtopics' => ['List Creation & Access', 'List Slicing & Operations', 'append(), extend(), insert(), pop()', 'Nested Lists & Algorithms']],
                ['number' => 8, 'title' => 'Tuples and Dictionaries in Python', 'description' => 'Immutability of tuples, dictionary key-value mapping, methods, and dictionary comprehensions.', 'subtopics' => ['Tuple Operations & Immutability', 'Tuple Packing and Unpacking', 'Dictionary Creation & Access', 'keys(), values(), items(), get()', 'Applications of Dictionaries']],
                ['number' => 9, 'title' => 'Python Modules and Standard Library', 'description' => 'Importing modules, math module, random module, statistics module, and user-defined packages.', 'subtopics' => ['What is a Python Module', 'import Statements & Aliasing', 'math & random Modules', 'statistics Module', 'Creating User-Defined Modules']],
                ['number' => 10, 'title' => 'Society, Law and Ethics in Digital Era', 'description' => 'Digital footprints, intellectual property rights, open-source software, cyber safety, and IT Act.', 'subtopics' => ['Digital Footprint & Privacy', 'Intellectual Property Rights (IPR)', 'Open Source Licenses (GPL, Apache)', 'Cybercrimes & Phishing', 'Indian IT Act & E-waste Management']],
            ]],

            // Class 12 Computer Science
            ['class' => 12, 'subject' => 'Computer Science', 'chapters' => [
                ['number' => 7, 'title' => 'Interface Python with SQL Database', 'description' => 'Connecting Python to MySQL database, executing queries, cursor objects, and committing changes.', 'subtopics' => ['mysql.connector Installation & Setup', 'Establishing Database Connection', 'Creating Cursor Object', 'Executing SELECT, INSERT, UPDATE', 'commit(), fetchone(), fetchall()', 'Parameterised Queries & Security']],
                ['number' => 8, 'title' => 'Cyber Safety, IT Act and Societal Impacts', 'description' => 'Data privacy, cyber stalking, hacking vs ethical hacking, identity theft, and cyber ethics.', 'subtopics' => ['Cyber Safety Principles', 'Malware (Viruses, Trojans, Worms)', 'Firewalls and HTTPS Security', 'Digital Identity Theft & Preventative Measures', 'IT Act 2000 & Net Neutrality']],
            ]],

            // Class 11 English Core
            ['class' => 11, 'subject' => 'English Core', 'chapters' => [
                ['number' => 5, 'title' => 'Childhood & Father to Son', 'description' => 'Poetic analysis of innocence loss, generational divide, and emotional alienation.', 'subtopics' => ['Theme of Markus Natten\'s Childhood', 'Poetic Devices & Imagery', 'Father to Son: Alienation Theme', 'Comparative Poetic Analysis']],
                ['number' => 6, 'title' => 'The Summer of the Beautiful White Horse', 'description' => 'Story of Aram, Mourad, the Garoghlanian tribe, and honesty vs youthful desire.', 'subtopics' => ['Character Sketches of Mourad & Aram', 'Garoghlanian Tribal Code of Honour', 'The White Horse Mystery', 'Themes of Trust & Moral Integrity']],
                ['number' => 7, 'title' => 'The Address & Mother\'s Day', 'description' => 'Post-war trauma of loss and memory; humorous domestic critique of family roles.', 'subtopics' => ['Marga Minco\'s Post-War Reflections', 'Significance of The Address', 'J.B. Priestley\'s Satire in Mother\'s Day', 'Empowerment & Family Dynamics']],
                ['number' => 8, 'title' => 'Advanced Writing Skills & Grammar', 'description' => 'Note making, title formulation, abbreviations, formal letters, and speech writing.', 'subtopics' => ['Note Making Format & Mechanics', 'Indentations & Abbreviation Key', 'Formal Letters to Editor & Authorities', 'Speech & Debate Formulation']],
            ]],

            // Class 12 English Core
            ['class' => 12, 'subject' => 'English Core', 'chapters' => [
                ['number' => 5, 'title' => 'Indigo & A Roadside Stand', 'description' => 'Mahatma Gandhi\'s Champaran satyagraha; Robert Frost\'s critique of urban-rural divide.', 'subtopics' => ['Louis Fischer\'s Account of Champaran', 'Gandhi\'s Principles of Non-Violent Civil Disobedience', 'Robert Frost\'s A Roadside Stand Themes', 'Plea for Rural Economic Justice']],
                ['number' => 6, 'title' => 'Poets and Pancakes & Aunt Jennifer\'s Tigers', 'description' => 'Gemini Studios behind-the-scenes; Adrienne Rich\'s feminist critique of patriarchy.', 'subtopics' => ['Asokamitran\'s Humorous Satire at Gemini Studios', 'Office Boy & Subbu Portraits', 'Aunt Jennifer\'s Tigers Symbolism', 'Feminine Resilience & Artistic Permanence']],
                ['number' => 7, 'title' => 'The Third Level & The Tiger King', 'description' => 'Escapism and psychological coping; satire on feudal pride and human folly.', 'subtopics' => ['Jack Finney\'s Grand Central Metaphor', 'Psychological Concept of Escapism', 'Kalki\'s Satire in The Tiger King', 'Fate, Hubris & Dramatic Irony']],
                ['number' => 8, 'title' => 'Journey to the End of the Earth & The Enemy', 'description' => 'Antarctica environmental expedition; Dr. Sadao\'s moral dilemma during WWII.', 'subtopics' => ['Tishani Doshi\'s Antarctic Perspective', 'Global Climate Warning Signs', 'Pearl S. Buck\'s The Enemy Context', 'Humanitarian Duty vs National Loyalty']],
                ['number' => 9, 'title' => 'On the Face of It & Memories of Childhood', 'description' => 'Derry and Mr. Lamb\'s bond; Zitkala-Sa and Bama on systemic discrimination.', 'subtopics' => ['Derry & Mr. Lamb\'s Dialogue', 'Overcoming Physical Disability Stigma', 'The Cutting of My Long Hair (Zitkala-Sa)', 'We Too Are Human Beings (Bama)', 'Social Resistance through Education']],
                ['number' => 10, 'title' => 'Advanced Composition & Board Writing Skills', 'description' => 'Notice drafting, formal and informal invitations, application for job, and articles.', 'subtopics' => ['Notice Writing Format & Marking Scheme', 'Formal & Informal Invitations & Replies', 'Job Application with Detailed Bio-data/Resume', 'Analytical Article & Report Writing']],
            ]],
        ];

        foreach ($additions as $entry) {
            $class = AcademicClass::where('grade_level', $entry['class'])->first();
            if (!$class) continue;

            $subject = AcademicSubject::where('class_id', $class->id)->where('name', $entry['subject'])->first();
            if (!$subject) continue;

            foreach ($entry['chapters'] as $ch) {
                $topic = CurriculumTopic::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'chapter_number' => $ch['number'],
                    ],
                    [
                        'title' => $ch['title'],
                        'description' => $ch['description'],
                        'subtopics' => $ch['subtopics'],
                    ]
                );

                $this->seedBloomActivities($topic);
            }
        }
    }

    /**
     * Seed Bloom's Taxonomy cognitive progression activities (Levels 1 to 6) for a chapter.
     */
    protected function seedBloomActivities(CurriculumTopic $topic): void
    {
        $verbs = [
            1 => ['Define, List, Recall, State, Identify', "Recall and state the foundational definitions, laws, formulas, and terminology of {$topic->title}."],
            2 => ['Explain, Describe, Clarify, Interpret, Summarize', "Explain the core concepts and mechanisms of {$topic->title} in your own words, comparing underlying principles."],
            3 => ['Apply, Calculate, Solve, Demonstrate, Execute', "Apply theoretical principles, formulas, and concepts of {$topic->title} to solve numerical or situational problems."],
            4 => ['Analyze, Differentiate, Investigate, Deconstruct', "Critically analyze cause-and-effect relationships, component parts, and practical limitations within {$topic->title}."],
            5 => ['Evaluate, Critique, Justify, Assess, Validate', "Evaluate competing approaches, assess theoretical models, and justify conclusions based on evidence in {$topic->title}."],
            6 => ['Create, Design, Synthesize, Formulate, Construct', "Design an innovative conceptual model, experiment, or synthesized project based on principles of {$topic->title}."],
        ];

        for ($level = 1; $level <= 6; $level++) {
            $meta = TopicBloomActivity::getLevelMetadata($level);
            [$actionVerbs, $defaultPrompt] = $verbs[$level];

            TopicBloomActivity::firstOrCreate(
                [
                    'topic_id' => $topic->id,
                    'bloom_level' => $level,
                ],
                [
                    'level_name' => $meta['name'] ?? 'Level ' . $level,
                    'verb_keywords' => $actionVerbs,
                    'activity_prompt' => $defaultPrompt,
                    'rubric_guide' => "Demonstrate conceptual clarity, precise terminology, logical structure, and standard academic conventions for Bloom Level " . $level . ".",
                ]
            );
        }
    }

    /**
     * Get the master curriculum data dictionary for Classes 1 to 12.
     */
    private function getCurriculumData(): array
    {
        return [
            1 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-01',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Shapes and Space',
                                'description' => 'Spatial awareness: Inside, Outside, Bigger, Smaller, Top and Bottom.',
                                'subtopics' => [
                                    'Inside-Outside',
                                    'Nearer-Farther',
                                    'Shapes around us',
                                    'Rolling and Sliding',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Numbers from One to Nine',
                                'description' => 'Cardinal and ordinal numbers, counting collections up to 9.',
                                'subtopics' => [
                                    'As many as',
                                    'Counting 1 to 9',
                                    'More or Less',
                                    'Number names',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Addition Basics',
                                'description' => 'Combining groups of objects and simple one-digit additions.',
                                'subtopics' => [
                                    'One more',
                                    'Count together',
                                    'Adding zero',
                                    'Addition sums',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Subtraction Basics',
                                'description' => 'Taking away from collections up to 9 with pictorial representations.',
                                'subtopics' => [
                                    'Take away',
                                    'Left over',
                                    'Subtracting zero',
                                    'Word problems',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Numbers from Ten to Twenty',
                                'description' => 'Introduction to place value with bundles of tens and single ones.',
                                'subtopics' => [
                                    'Making 10',
                                    'Numbers 11 to 20',
                                    'Counting groups',
                                    'Number line basics',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Time & Measurement',
                                'description' => 'Understanding routines, day/night cycles, and length comparison.',
                                'subtopics' => [
                                    'Daily activities',
                                    'Morning, Afternoon, Evening',
                                    'Longer-Shorter',
                                    'Heavier-Lighter',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Patterns and Money',
                                'description' => 'Recognizing alternating geometric patterns and Indian currency coins.',
                                'subtopics' => [
                                    'Shape patterns',
                                    'Number sequences',
                                    'Indian coins (1, 2, 5, 10)',
                                    'Rupee notes',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Environmental Studies (EVS)',
                        'code' => 'EVS-01',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'All About Me & My Family',
                                'description' => 'Identity, self-awareness, roles of family members.',
                                'subtopics' => [
                                    'My Name & Birthday',
                                    'Sense Organs',
                                    'Nuclear and Joint Families',
                                    'Helping at home',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Plants and Animals Around Us',
                                'description' => 'Identifying common domestic animals, birds, and flora.',
                                'subtopics' => [
                                    'Pet animals',
                                    'Wild animals',
                                    'Leaves and Flowers',
                                    'Taking care of pets',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Healthy Habits & Cleanliness',
                                'description' => 'Personal hygiene, brushing, washing hands, and balanced meals.',
                                'subtopics' => [
                                    'Washing hands',
                                    'Clean clothes',
                                    'Healthy food vs junk',
                                    'Rest and Sleep',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Our Clothes and Homes',
                                'description' => 'Need for shelter, types of roofs, summer cottons, winter woolens.',
                                'subtopics' => [
                                    'Types of Houses',
                                    'Rooms in House',
                                    'Seasonal Clothing',
                                    'Care of Clothes',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Safety First & Good Manners',
                                'description' => 'Safety at home, playground rules, zebra crossing, polite courtesy words.',
                                'subtopics' => [
                                    'Traffic Light Signals',
                                    'Zebra Crossing',
                                    'Safety with Sharp Objects',
                                    'Polite Words',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-01',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Phonics and Alphabet World',
                                'description' => 'Vowel and consonant phonetic sounds, lowercase and uppercase tracing.',
                                'subtopics' => [
                                    'Phonic sounds A-Z',
                                    'Three-letter CVC words',
                                    'Sight words',
                                    'Tracing lines',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'The Magic of Rhymes & Stories',
                                'description' => 'Recitation, listening comprehension, and dramatic roleplay.',
                                'subtopics' => [
                                    'Twinkle Twinkle',
                                    'The Thirsty Crow',
                                    'Action songs',
                                    'Vocabulary building',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Naming Words (Nouns)',
                                'description' => 'Identifying persons, places, animals, and everyday objects.',
                                'subtopics' => [
                                    'Person Names',
                                    'Place Names',
                                    'Animal Names',
                                    'Object Names',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Simple Action Words',
                                'description' => 'Everyday verbs: run, jump, read, write, sing, sleep.',
                                'subtopics' => [
                                    'Identifying Actions',
                                    'Doing Words in Pictures',
                                    'Simple Sentences',
                                    'Body Action Verbs',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-01',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Swar Aur Vyanjan (Varnamala)',
                                'description' => 'Hindi vowels, consonants, sound articulation, letter recognition.',
                                'subtopics' => [
                                    'Swar (अ से अः)',
                                    'Vyanjan (क से ज्ञ)',
                                    'Chitra Pehchan',
                                    'Varna Abhyas',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Do Aur Teen Varna Ke Shabd',
                                'description' => 'Unmarked word building with 2 and 3 consonants.',
                                'subtopics' => [
                                    'Do Varna Ke Shabd',
                                    'Teen Varna Ke Shabd',
                                    'Sahi Shabd Chunna',
                                    'Chhote Vakya',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Chaar Varna Ke Shabd Aur Aa Ki Matra',
                                'description' => 'Four letter words and introduction to Aa ki matra.',
                                'subtopics' => [
                                    'Chaar Varna Ke Shabd',
                                    'Aa Ki Matra (ा)',
                                    'Shabd Rachna',
                                    'Pathan Abhyas',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Balgeet Aur Kahani',
                                'description' => 'Melodious Hindi nursery poems and moral folk stories.',
                                'subtopics' => [
                                    'Chanda Mama Balgeet',
                                    'Sher Aur Chuha',
                                    'Kavita Recitation',
                                    'Prashna Uttar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            2 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-02',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'What is Long, What is Round?',
                                'description' => 'Classifying shapes by straight and curved edges, roll versus slide.',
                                'subtopics' => [
                                    'Round objects',
                                    'Long objects',
                                    'Tower building',
                                    'Coin spinning',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Counting in Groups',
                                'description' => 'Counting by 2s, 5s, and 10s up to 100 with pair concepts.',
                                'subtopics' => [
                                    'Pairs of shoes',
                                    'Tens and units',
                                    'Guessing totals',
                                    'Ascending/Descending order',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'How Much Can You Carry?',
                                'description' => 'Comparative weights using balance scales and simple reasoning.',
                                'subtopics' => [
                                    'Lighter than',
                                    'Heavier than',
                                    'Balance beam balance',
                                    'Animal carrying capacities',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Counting in Tens & Hundreds',
                                'description' => 'Expanded place value representation up to 200.',
                                'subtopics' => [
                                    'Bundles of ten',
                                    'Flash cards',
                                    'Chanda Mama and stars',
                                    'Skip counting',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Patterns and Lines',
                                'description' => 'Straight, slanting, and curved lines, recurring decorative patterns.',
                                'subtopics' => [
                                    'Standing & Sleeping Lines',
                                    'Curved Lines in Art',
                                    'Number Series Patterns',
                                    'Rangoli Designs',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Footprints and 2D Shapes',
                                'description' => 'Tracing bases of cups, bowls, erasers to identify circles, squares, rectangles.',
                                'subtopics' => [
                                    'Tracing Footprints',
                                    'Circle, Square, Rectangle',
                                    'Counting Shapes in Pictures',
                                    'Creative Shape Drawings',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Jugs and Mugs (Capacity)',
                                'description' => 'Estimating capacity and liquid volume using household vessels.',
                                'subtopics' => [
                                    'Filling a bucket',
                                    'Cups to fill a bottle',
                                    'Conserving water',
                                    'Half and full',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Environmental Studies (EVS)',
                        'code' => 'EVS-02',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Our Neighborhood & Helpers',
                                'description' => 'Community facilities, doctors, teachers, police, and sanitation workers.',
                                'subtopics' => [
                                    'Hospital and Police post',
                                    'Community helpers',
                                    'Marketplaces',
                                    'Postal services',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Plants and Trees in Our Surroundings',
                                'description' => 'Roots, stems, flowers, herbs, shrubs, big shady trees, care of plants.',
                                'subtopics' => [
                                    'Herbs and Shrubs',
                                    'Climbers & Creepers',
                                    'Big Trees in Park',
                                    'Watering Plants Daily',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Animal Friends & Their Homes',
                                'description' => 'Habitats, pet care, wild sanctuaries, bird nests, aquatic creatures.',
                                'subtopics' => [
                                    'Domestic vs Wild',
                                    'Animal Shelters (Den, Burrow, Nest)',
                                    'Baby Animals',
                                    'Kindness to Animals',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Seasons and Weather',
                                'description' => 'Summer, Monsoon, Winter, Spring, and corresponding clothing and food.',
                                'subtopics' => [
                                    'Summer cottons',
                                    'Monsoon umbrellas',
                                    'Winter woolens',
                                    'Seasonal fruits',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Means of Transport & Safety',
                                'description' => 'Land, water, and air transportation with traffic signal rules.',
                                'subtopics' => [
                                    'Road safety',
                                    'Zebra crossings',
                                    'Trains and Boats',
                                    'Emergency numbers',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-02',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'First Day at School & Haldi’s Adventure',
                                'description' => 'School excitement poetry and the story of Haldi meeting giraffe Smiles.',
                                'subtopics' => [
                                    'School Readiness Verse',
                                    'Meeting Smiles the Giraffe',
                                    'Capital Letters & Punctuation',
                                    'Sight Word Spelling',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'I am Lucky! & I Want',
                                'description' => 'Gratitude verse for unique abilities and the monkey wishing to be different.',
                                'subtopics' => [
                                    'Gratitude Theme',
                                    'The Wise Woman & Magic Wand',
                                    'Use of Is, Am, Are',
                                    'Animal Sound Vocabulary',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'A Smile & The Wind and the Sun',
                                'description' => 'Power of a cheerful smile verse and Aesop fable of Wind versus Sun.',
                                'subtopics' => [
                                    'Smiles are Contagious',
                                    'Aesop Fable Moral',
                                    'Opposite Words',
                                    'Degrees of Comparison Intro',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Action Words and Describing Words',
                                'description' => 'Verbs with -ing, adjectives of color, size, and shape.',
                                'subtopics' => [
                                    'Doing Words with -ing',
                                    'Describing Words (Adjectives)',
                                    'Singular and Plural (-s, -es)',
                                    'Picture Composition',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-02',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Oont Chala Aur Bhaloo Ne Kheli Football',
                                'description' => 'Rhythmic camel stride verse and funny winter bear playing football.',
                                'subtopics' => [
                                    'Oont Chala Bal Kavita',
                                    'Bhaloo Aur Sher Ka Bachha',
                                    'Hasya Katha',
                                    'Varna Matra Abhyas',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Myan-Myan Aur Adhik Balwan Kaun?',
                                'description' => 'Childhood mouse dream verse and contest between Sun and Wind.',
                                'subtopics' => [
                                    'Myan-Myan Kavita',
                                    'Suraj Aur Hawa Ki Kahani',
                                    'Balwan Kaun Sandesh',
                                    'Vilom Shabd',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Dost Ki Madad Aur Bus Ke Neeche Bagh',
                                'description' => 'Panchatantra tortoise-fox friendship, thrilling story of tiger cub near bus.',
                                'subtopics' => [
                                    'Sachhe Dost Ki Pehchan',
                                    'Bagh Ka Bachha Kahani',
                                    'Pashu Sanrakshan',
                                    'Vakya Nirman',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Vyakaran: Sangya, Ling Aur Vachan',
                                'description' => 'Hindi nouns, masculine/feminine gender, singular and plural forms.',
                                'subtopics' => [
                                    'Sangya Ki Paribhasha',
                                    'Ling Parivartan',
                                    'Ek Vachan Se Bahuvachan',
                                    'Shuddh Vartani',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            3 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-03',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Where to Look From?',
                                'description' => 'Perspective drawings (top, side, front), mirror halves, and dot grid art.',
                                'subtopics' => [
                                    'Isometric perspectives',
                                    'Mirror symmetry',
                                    'Rangoli patterns',
                                    'Dot grid geometry',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Fun with Numbers',
                                'description' => 'Place values up to 999, number names, expanded forms, cricket centuries.',
                                'subtopics' => [
                                    'Hundreds, Tens, Ones',
                                    'Expanded notation',
                                    'Skip counting by 50s and 100s',
                                    'Odd and Even numbers',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Give and Take (Addition & Subtraction)',
                                'description' => 'Two and three digit additions, subtraction without and with borrowing.',
                                'subtopics' => [
                                    'Mental math tricks',
                                    'Column addition',
                                    'Regrouping subtraction',
                                    'Word problem strategies',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Long and Short',
                                'description' => 'Measuring in meters and centimeters, arm span, measuring tapes.',
                                'subtopics' => [
                                    'Meters to Centimeters',
                                    'Estimating lengths',
                                    'Real-world distance comparisons',
                                    'Map route scales',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Shapes and Designs',
                                'description' => 'Edges, corners, 5-piece and 7-piece tangrams, floor tiling patterns.',
                                'subtopics' => [
                                    'Properties of polygons',
                                    'Tangram puzzle solving',
                                    'Tessellations and tiling',
                                    'Weaving patterns',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Time Goes On',
                                'description' => 'Clocks, hours, minutes, calendar dates, leap years, family timelines.',
                                'subtopics' => [
                                    'Reading clock hands',
                                    'Elapsed time calculations',
                                    'Calendar patterns',
                                    'Historical timeline sequencing',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Can We Share? (Division Basics)',
                                'description' => 'Division as equal distribution and repeated subtraction.',
                                'subtopics' => [
                                    'Equal sharing groups',
                                    'Division symbol (÷)',
                                    'Quotient and Remainder',
                                    'Word problem division',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Environmental Studies (EVS)',
                        'code' => 'EVS-03',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Poonam’s Day Out',
                                'description' => 'Animal diversity, terrestrial, arboreal, aquatic fauna, movement types.',
                                'subtopics' => [
                                    'Animal locomotion',
                                    'Bird and insect calls',
                                    'Fingertip animal art',
                                    'Habitats and adaptations',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'The Plant Fairy',
                                'description' => 'Leaf margins, textures, medicinal herbs, bark rubbings, trunk diversity.',
                                'subtopics' => [
                                    'Leaf classification',
                                    'Herbs and medicinal plants',
                                    'Bark rubbing prints',
                                    'Trees in Indian culture',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Water O’ Water!',
                                'description' => 'Natural water cycle, reservoirs, water conservation, droughts, floods.',
                                'subtopics' => [
                                    'Rainwater sources',
                                    'States of water',
                                    'Water storage in villages',
                                    'Save every drop ethics',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Our First School (Family Traditions)',
                                'description' => 'Intergenerational learning, empathy, family resemblance, shared chores.',
                                'subtopics' => [
                                    'Family values',
                                    'Cultural customs',
                                    'Empathy for elders',
                                    'Division of labor at home',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Foods We Eat',
                                'description' => 'Regional Indian cuisines, staple crops, nutritional balance, festival feasts.',
                                'subtopics' => [
                                    'Grains: Wheat, Rice, Millets',
                                    'Regional food diversity',
                                    'Vitamins and energy food',
                                    'Food waste prevention',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Flying High (Birds and Feathers)',
                                'description' => 'Bird anatomy, beaks, flight mechanisms, claws, nesting architecture.',
                                'subtopics' => [
                                    'Types of beaks and diet',
                                    'Avian claws & perching',
                                    'Nest building materials',
                                    'Bird conservation',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-03',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Good Morning & The Magic Garden',
                                'description' => 'Celebration of morning light in poetry, enchanted garden prose.',
                                'subtopics' => [
                                    'Nature appreciation verse',
                                    'Fairy garden vocabulary',
                                    'Pronouns usage',
                                    'Sentence rewriting',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Bird Talk & Nina and the Baby Sparrows',
                                'description' => 'Bird conversation verse, touching story of Nina protecting baby sparrows.',
                                'subtopics' => [
                                    'Animal empathy story',
                                    'Rhyming word pairs',
                                    'Punctuation and speech marks',
                                    'Short paragraph writing',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Little by Little & The Enormous Turnip',
                                'description' => 'Growth of oak acorn poem, teamwork in pulling giant turnip.',
                                'subtopics' => [
                                    'Growth metaphors in verse',
                                    'Teamwork folklore',
                                    'Collective nouns',
                                    'Opposite words',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Sea Song & A Little Fish Story',
                                'description' => 'Shell melody ocean poem, self-acceptance tale of small fish escaping nets.',
                                'subtopics' => [
                                    'Ocean sensory verse',
                                    'Moral of self-worth',
                                    'Prepositions (in, on, under)',
                                    'Past tense verbs',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Grammar: Tenses, Prepositions & Letters',
                                'description' => 'Simple present and past tenses, common prepositions, informal letters.',
                                'subtopics' => [
                                    'Present vs Past Tense',
                                    'Prepositions of Position',
                                    'Informal Letter to Friend',
                                    'Reading Comprehension',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-03',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Kakku Aur Shekhibaz Makkhi',
                                'description' => 'Humorous verse on teasing, witty folktale about boastful fly and clever fox.',
                                'subtopics' => [
                                    'Hasya Kavita Bhaav',
                                    'Panchatantra Chaturai',
                                    'Shabdaarth Aur Vakya',
                                    'Paryaywachi Shabd',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Chand Wali Amma Aur Bahadur Bitto',
                                'description' => 'Folk imagination in rural folklore, courage of female farmer outsmarting tiger.',
                                'subtopics' => [
                                    'Lokkatha Ka Soundarya',
                                    'Bahadur Bitto Ki Virata',
                                    'Muhavaron Ka Prayog',
                                    'Charitra Chitran',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Suraj Aur Chand Upar Kyon Gaye',
                                'description' => 'Mythological origin story of sky bodies, friendship of water and sun.',
                                'subtopics' => [
                                    'Pauranik Kalpana',
                                    'Prakriti Tatva',
                                    'Samvaad Lekhan',
                                    'Vakya Shuddhi',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Vyakaran: Sarvanam, Kriya Aur Patra Lekhan',
                                'description' => 'Hindi pronouns, action verbs, formal application to school principal.',
                                'subtopics' => [
                                    'Sarvanam Ke Bhed',
                                    'Kriya Ki Pehchan',
                                    'Bimari Ki Chhuti Ka Patra',
                                    'Anuched Lekhan',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            4 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-04',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Building with Bricks',
                                'description' => '3D brick patterns, arches, circular floor mosaics, isometric drawing.',
                                'subtopics' => [
                                    'Jaali and Jharokha designs',
                                    'Brick arch geometry',
                                    'Estimating brick quantities',
                                    'Kiln firing math',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Long and Short',
                                'description' => 'Metric conversions (km, m, cm), Indian national sports records, marathons.',
                                'subtopics' => [
                                    'Kilometer calculations',
                                    'High jump & Long jump metrics',
                                    'Unit conversions table',
                                    'Word problems on distances',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'A Trip to Bhopal',
                                'description' => 'Trip budgeting, bus fuel consumption, boat ride schedules, division on wheels.',
                                'subtopics' => [
                                    'Diesel cost division',
                                    'Lake boat ticketing math',
                                    'Clock scheduling intervals',
                                    'Multi-step mental arithmetic',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Tick-Tick-Tick',
                                'description' => 'Analogue to 24-hour digital clock conversion, railway timetables, shelf-life dates.',
                                'subtopics' => [
                                    '12 vs 24 hour clocks',
                                    'Train journey durations',
                                    'Manufacturing and expiry dates',
                                    'Age calculation algorithms',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'The Way The World Looks',
                                'description' => 'Aerial perspectives, topographic layouts, compass orientation, grid mapping.',
                                'subtopics' => [
                                    'Top vs Side perspective',
                                    'Navigating town maps',
                                    'Grid coordinate systems',
                                    'Compass four cardinal directions',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'The Junk Seller (Profit and Loss)',
                                'description' => 'Money arithmetic, interest calculations, multiplication box methods, invoices.',
                                'subtopics' => [
                                    'Cost price and selling price',
                                    'Loan simple interest concept',
                                    'Lattice multiplication',
                                    'Cash memo preparation',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Jugs and Mugs (Metric Capacity)',
                                'description' => 'Measuring in liters and milliliters, medical dosage dilution, household water logs.',
                                'subtopics' => [
                                    '1000 mL = 1 Liter',
                                    'Medical syringe calibrations',
                                    'Water audit calculations',
                                    'Capacity word puzzles',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Environmental Studies (EVS)',
                        'code' => 'EVS-04',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Going to School Across India',
                                'description' => 'Bamboo bridges of Assam, Kerala vallams, Ladakh trolley ropeways, deserts.',
                                'subtopics' => [
                                    'Geographical terrain challenges',
                                    'Indigenous transport inventions',
                                    'Right to Education (RTE)',
                                    'Disability accessibility',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Ear to Ear (Animal Adaptations)',
                                'description' => 'External vs internal ears, skin hair patterns, viviparous vs oviparous animals.',
                                'subtopics' => [
                                    'Auditory anatomy of animals',
                                    'Epidermal camouflage hair',
                                    'Mammalian live birth vs eggs',
                                    'Endangered species protection',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'A Day with Nandu (Elephant Social Structure)',
                                'description' => 'Matriarchal elephant herds, dietary bulk intake, conservation sanctuaries.',
                                'subtopics' => [
                                    'Matriarch herd leadership',
                                    'Foraging and dietary volume',
                                    'Mud cooling thermoregulation',
                                    'Anti-poaching legislation',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'The Story of Amrita (Bishnoi Conservation)',
                                'description' => 'Sacrifice for Khejri trees in Rajasthan, Bishnoi environmental principles.',
                                'subtopics' => [
                                    'Khejri tree ecological value',
                                    'Bishnoi non-violence to trees',
                                    'History of Chipko inspiration',
                                    'Desert ecosystem balance',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Anita and the Honeybees',
                                'description' => 'Girl star trailblazer, apiary science, queen bee, drone, worker roles.',
                                'subtopics' => [
                                    'Overcoming gender bias',
                                    'Beekeeping box management',
                                    'Hive caste roles and honey making',
                                    'Community self-help groups',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Omana’s Journey (Indian Railways & Geography)',
                                'description' => 'Train expedition from Gujarat to Kerala, Western Ghats biodiversity, culture.',
                                'subtopics' => [
                                    'Railway infrastructure & signals',
                                    'Western Ghat tunnels & bridges',
                                    'Paddy fields & coastal crops',
                                    'Culinary diversity of states',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-04',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Wake Up! & Neha’s Alarm Clock',
                                'description' => 'Morning song celebrating nature awakening, story of inner circadian rhythms.',
                                'subtopics' => [
                                    'Morning lyrical rhythm',
                                    'Body biological clock',
                                    'Adverbs of time',
                                    'Descriptive writing',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Noses & The Little Fir Tree',
                                'description' => 'Humorous poem on facial features, parable on appreciating self-worth.',
                                'subtopics' => [
                                    'Humorous verse meter',
                                    'Parable of natural design',
                                    'Prefixes un- and dis-',
                                    'Story sequencing',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Run! & Nasruddin’s Aim',
                                'description' => 'Outdoor physical vitality verse, humorous anecdote of Nasruddin archery wit.',
                                'subtopics' => [
                                    'Sports and active play',
                                    'Wit and comedic timing',
                                    'Modal verbs (can, could, must)',
                                    'Dialogue delivery',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Why? & Alice in Wonderland',
                                'description' => 'Inquisitive curiosity verse, Lewis Carroll classic dreamland wonderland passage.',
                                'subtopics' => [
                                    'Curious Wh- questions',
                                    'Victorian nonsense literature',
                                    'Prepositions and conjunctions',
                                    'Creative diary entry',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Formal Writing: Applications & Notices',
                                'description' => 'Format of official school leave applications, notice board drafting skills.',
                                'subtopics' => [
                                    'Application to Principal format',
                                    'Notice writing guidelines',
                                    'Editing for spelling & grammar',
                                    'Reading comprehension skills',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-04',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Man Ke Bhole-Bhale Badal',
                                'description' => 'Imaginative clouds poetry capturing shapes of camels, fairies, elephants.',
                                'subtopics' => [
                                    'Kavita Ka Ras Aur Alankar',
                                    'Varsha Ritu Ka Varnan',
                                    'Tukbandi Shabd',
                                    'Chitra Varnan',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Jaisa Sawal Waisa Jawab (Birbal Ki Chaturai)',
                                'description' => 'Akbar and Birbal intellectual contest testing wisdom and wit against rival courtiers.',
                                'subtopics' => [
                                    'Akbar-Birbal Samvaad',
                                    'Hazirjawabi Aur Tark',
                                    'Vyakaran Abhyas',
                                    'Shabdaarth',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Kirmich Ki Gend Aur Papa Jab Bachhe The',
                                'description' => 'Neighborhood summer cricket ball discovery, childhood changing career dreams.',
                                'subtopics' => [
                                    'Khel Ki Bhavna',
                                    'Bade Hote Sapne',
                                    'Vyavasayik Ichhayen',
                                    'Kahani Ka Sandesh',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Vyakaran: Karak, Muhavare Aur Patra Lekhan',
                                'description' => 'Eight Hindi cases (Karak), idiomatic phrases, formal application drafting.',
                                'subtopics' => [
                                    'Karak Ke 8 Bhed',
                                    'Prasiddha Muhavaron Ka Prayog',
                                    'Pradhanacharya Ko Chhuti Patra',
                                    'Vichar Pradhan Nibandh',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            5 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-05',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The Fish Tale',
                                'description' => 'Indian place value up to crores, speed = distance / time, fishery economics.',
                                'subtopics' => [
                                    'Number systems up to Crores',
                                    'Speed, Distance, Time formulas',
                                    'Loans and bank savings groups',
                                    'Catch yield profit calculation',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Shapes and Angles',
                                'description' => 'Degrees, right angle, acute, obtuse, straight angles, clock hands angles.',
                                'subtopics' => [
                                    'Protractor angle measurement',
                                    'Angles in yoga postures',
                                    'Degree clock construction',
                                    'Interior angles of polygons',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'How Many Squares? (Area & Perimeter)',
                                'description' => 'Unit square grids, perimeter versus area, irregular shape boundary estimation.',
                                'subtopics' => [
                                    'Perimeter of composite shapes',
                                    'Area formulas for rectangles',
                                    'Grid counting for leaves',
                                    'Tessellation areas',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Parts and Wholes (Fractions)',
                                'description' => 'Fractional parts of shapes, equivalent fractions, fractions on money and time.',
                                'subtopics' => [
                                    'Numerator and Denominator',
                                    'Equivalent fraction generation',
                                    'Mixed and improper fractions',
                                    'Word problems on parts',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Does it Look the Same? (Symmetry)',
                                'description' => 'Line symmetry, rotational symmetry (1/2, 1/3, 1/4, 1/6 turns), windmill blades.',
                                'subtopics' => [
                                    'Rotational angle symmetry',
                                    '1/4 and 1/2 turn drawings',
                                    'Symmetric alphabet designs',
                                    'Mirror reflection tests',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Be My Multiple, I’ll Be Your Factor',
                                'description' => 'Factors, multiples, prime numbers, factor trees, LCM and HCF.',
                                'subtopics' => [
                                    'Divisibility rules',
                                    'Sieve of Eratosthenes',
                                    'Factor tree decomposition',
                                    'LCM and HCF methods',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Boxes and Sketches',
                                'description' => '3D nets of cubes and cuboids, deep drawings, bridge elevations, floor maps.',
                                'subtopics' => [
                                    '11 Nets of a cube',
                                    'Perspective drawing rules',
                                    'Floor plan interpretation',
                                    'Isometric paper sketching',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Mapping Your Way',
                                'description' => 'Map scales (1 cm = 100 km), Rajpath New Delhi route planning, aerial grids.',
                                'subtopics' => [
                                    'Cartographic scale ratios',
                                    'Navigating capital avenues',
                                    'Enlarging on square grids',
                                    'State geographical boundaries',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Environmental Studies (EVS)',
                        'code' => 'EVS-05',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Super Senses (Sensory Biology)',
                                'description' => 'Sensory adaptations in ants, eagles, dogs, bats, tiger territory patrolling.',
                                'subtopics' => [
                                    'Ant pheromone pathways',
                                    'Binocular visual acuity in eagles',
                                    'Canine olfactory sensitivity',
                                    'Sanctuary anti-poaching measures',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'A Snake Charmer’s Story',
                                'description' => 'Kalbelia folk dance and wisdom, venomous species, antivenom serums.',
                                'subtopics' => [
                                    'Kalbelia musical been traditions',
                                    'Cobras, Kraits, Russell vipers',
                                    'First aid for snakebites',
                                    'Ecosystem rodent control',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'From Tasting to Digesting',
                                'description' => 'Tongue taste buds, salivary enzymes, gastric juices, Dr. Beaumont experiments.',
                                'subtopics' => [
                                    'Papillae taste receptors',
                                    'Digestion in mouth and stomach',
                                    'Dr. Beaumont gastric discoveries',
                                    'Oral rehydration solution (ORS)',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Mangoes Round the Year (Food Technology)',
                                'description' => 'Mamidi tandra production, food spoilage bacteria, pickling, preservation.',
                                'subtopics' => [
                                    'Mamidi tandra sun-drying',
                                    'Bacterial and fungal spoilage',
                                    'Preservation using salt and sugar',
                                    'Food packaging safety dates',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Seeds and Seeds',
                                'description' => 'Seed anatomy, germination conditions, dispersal agents (wind, water, fur).',
                                'subtopics' => [
                                    'Embryo, cotyledon, seed coat',
                                    'Moisture and oxygen germination',
                                    'Explosive pod dispersal',
                                    'Velcro biomimicry discovery',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Every Drop Counts (Water Conservation)',
                                'description' => 'Ghadsisar lake cascading design, stepwells (bawlis), rainwater harvesting.',
                                'subtopics' => [
                                    'Interconnected lake systems',
                                    'Rajasthan bawli architecture',
                                    'Rooftop rainwater harvesting',
                                    'Jal Swaraj community water',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-05',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Ice-Cream Man & Wonderful Waste!',
                                'description' => 'Sensory summer verse, Kerala royal court recipe of avial from leftovers.',
                                'subtopics' => [
                                    'Summer sensory diction',
                                    'Zero-waste culinary wisdom',
                                    'Avial recipe origin',
                                    'Adjectives of texture and taste',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Teamwork & Flying Together',
                                'description' => 'Synergy in team verse, wise old gander Panchatantra tale of trapped flock.',
                                'subtopics' => [
                                    'Power of shared cooperation',
                                    'Panchatantra flock escape',
                                    'Contractions (can’t, shouldn’t)',
                                    'Public speaking debate',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'My Shadow & Robinson Crusoe Discovers a Footprint',
                                'description' => 'Childhood shadow verse, desert island footprint suspense classic.',
                                'subtopics' => [
                                    'Light and shadow poetic play',
                                    'Suspense adventure narrative',
                                    'Degrees of comparison',
                                    'Descriptive setting writing',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Crying & My Elder Brother (Bade Bhai Sahab)',
                                'description' => 'Emotional resilience poetry, Premchand touching tale of two brothers.',
                                'subtopics' => [
                                    'Healthy emotional expression',
                                    'Premchand sibling classic',
                                    'Idiomatic expressions',
                                    'Character analysis essay',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'The Talkative Barber & Topsy-Turvy Land',
                                'description' => 'Humorous Arabian Nights tale, whimsical upside-down wonderland poem.',
                                'subtopics' => [
                                    'Arabian Nights storytelling',
                                    'Nonsense verse appreciation',
                                    'Reported speech conversion',
                                    'Narrative writing mastery',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-05',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Rakh Ki Rassi Aur Faslon Ke Tyohar',
                                'description' => 'Tibetan folktale of wisdom overcoming trickery, harvest festivals of India.',
                                'subtopics' => [
                                    'Tibbat Ki Lokkatha',
                                    'Makar Sankranti, Bihu, Pongal',
                                    'Bhasha Aur Sanskriti',
                                    'Shabdaarth',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Khilonevala Aur Eidgah (Premchand)',
                                'description' => 'Subhadra Kumari Chauhan poetic joy, Hamid selfless chimta gift for grandmother.',
                                'subtopics' => [
                                    'Khilonevala Kavita Bhavarth',
                                    'Premchand Ki Eidgah',
                                    'Naitik Mulya Aur Tyag',
                                    'Charitra Chitran',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Nanhe Fankar Aur Dakiyen Ki Kahani',
                                'description' => '10-year old stone carver in Fatehpur Sikri, journey of a Himalayan postman.',
                                'subtopics' => [
                                    'Mughal Shilpkala',
                                    'Daakiye Ka Jeevan Sangharsh',
                                    'Shabdaarth Aur Vakya',
                                    'Samvaad Lekhan',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Vyakaran: Sandhi, Samas Aur Nibandh Lekhan',
                                'description' => 'Basic vowel sandhi, samas compound words, structured argumentative essays.',
                                'subtopics' => [
                                    'Swar Sandhi Parichay',
                                    'Tatpurush Samas',
                                    'Rashtriya Ekta Nibandh',
                                    'Anopacharik Patra',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            6 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-06',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Knowing Our Numbers',
                                'description' => 'Indian and International numeration, Roman numerals, estimation, large numbers.',
                                'subtopics' => [
                                    'Indian vs International System',
                                    'Roman Numerals',
                                    'Estimation by Rounding Off',
                                    'Bracket Rules & BODMAS',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Whole Numbers',
                                'description' => 'Natural numbers, predecessor/successor, number line operations, properties of whole numbers.',
                                'subtopics' => [
                                    'Closure, Commutative, Associative',
                                    'Distributive Property',
                                    'Identity for Addition/Multiplication',
                                    'Patterns of Whole Numbers',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Playing with Numbers',
                                'description' => 'Factors and multiples, prime/composite, divisibility tests, prime factorization, HCF and LCM.',
                                'subtopics' => [
                                    'Divisibility Rules for 2,3,4,5,6,8,9,11',
                                    'Sieve of Eratosthenes',
                                    'Prime Factorization',
                                    'HCF and LCM Applications',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Basic Geometrical Ideas',
                                'description' => 'Points, lines, rays, intersecting/parallel lines, curves, polygons, angles, triangles, circles.',
                                'subtopics' => [
                                    'Line, Line Segment, Ray',
                                    'Polygons and Vertices',
                                    'Parts of Angle and Triangle',
                                    'Radius, Diameter, Chord, Arc',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Understanding Elementary Shapes',
                                'description' => 'Measuring line segments, angles classification, perpendicular lines, triangles classification, quadrilaterals, 3D shapes.',
                                'subtopics' => [
                                    'Acute, Right, Obtuse, Reflex',
                                    'Clock Angles & Revolution',
                                    'Scalene, Isosceles, Equilateral',
                                    'Faces, Edges, Vertices of 3D',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Integers',
                                'description' => 'Need for negative numbers, representation on number line, ordering, addition and subtraction of integers.',
                                'subtopics' => [
                                    'Positive & Negative Concept',
                                    'Integers on Number Line',
                                    'Addition using Number Line',
                                    'Rules of Integer Arithmetic',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Fractions',
                                'description' => 'Fraction on number line, proper, improper, mixed fractions, equivalent fractions, simplest form, comparing fractions.',
                                'subtopics' => [
                                    'Types of Fractions',
                                    'Equivalent Fractions',
                                    'Like and Unlike Fractions',
                                    'Addition & Subtraction of Fractions',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Decimals',
                                'description' => 'Tenths, hundredths, place value table, decimals on number line, money/metric conversions, addition and subtraction.',
                                'subtopics' => [
                                    'Decimal Place Value',
                                    'Decimals as Fractions',
                                    'Money, Length, Weight Units',
                                    'Decimal Arithmetic',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Data Handling',
                                'description' => 'Recording data, tally marks, organizing data, pictograph representation, bar graph construction.',
                                'subtopics' => [
                                    'Tally Marks Tables',
                                    'Interpreting Pictographs',
                                    'Constructing Bar Graphs',
                                    'Real-world Survey Data',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Mensuration',
                                'description' => 'Perimeter of rectangle, square, regular polygons; Area of rectangle, square; Unit conversions.',
                                'subtopics' => [
                                    'Perimeter of Closed Figures',
                                    'Area Formula (l × b)',
                                    'Area of Regular Polygons',
                                    'Word Problems on Fencing/Tiles',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Algebra',
                                'description' => 'Introduction to variables through matchstick patterns, algebraic expressions, linear equations in one variable.',
                                'subtopics' => [
                                    'Matchstick Pattern Rules',
                                    'Variables and Constants',
                                    'Forming Simple Expressions',
                                    'Solving Equations by Trial & Error',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Ratio and Proportion',
                                'description' => 'Comparison by division, equivalent ratios, proportion conditions, unitary method.',
                                'subtopics' => [
                                    'Ratio in Simplest Form',
                                    'Proportion Extremes & Means',
                                    'Unitary Method Applications',
                                    'Word Problems on Speed & Cost',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Science',
                        'code' => 'SCI-06',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Components of Food',
                                'description' => 'Nutrients: carbohydrates, fats, proteins, vitamins, minerals; dietary fibers; balanced diet; deficiency diseases.',
                                'subtopics' => [
                                    'Iodine, Biuret, Paper Fat Tests',
                                    'Functions of Nutrients',
                                    'Balanced Diet Formulation',
                                    'Deficiency Diseases (Scurvy, Rickets)',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Sorting Materials into Groups',
                                'description' => 'Properties of materials: appearance, hardness, solubility, floating/sinking, transparency.',
                                'subtopics' => [
                                    'Lustre and Texture',
                                    'Solubility in Water',
                                    'Density and Buoyancy',
                                    'Transparent, Translucent, Opaque',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Separation of Substances',
                                'description' => 'Handpicking, threshing, winnowing, sieving, sedimentation, decantation, filtration, evaporation, condensation.',
                                'subtopics' => [
                                    'Dry Separation Techniques',
                                    'Sedimentation & Decantation',
                                    'Filtration through Paper',
                                    'Evaporation & Salt Extraction',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Getting to Know Plants',
                                'description' => 'Herbs, shrubs, trees; stem transport; leaf venation (reticulate/parallel); flower anatomy; root systems (tap/fibrous).',
                                'subtopics' => [
                                    'Herb, Shrub, Tree Morphology',
                                    'Transpiration Experiment',
                                    'Tap Root vs Fibrous Root',
                                    'Sepals, Petals, Stamens, Pistil',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Body Movements',
                                'description' => 'Human skeletal system, joints (ball-and-socket, hinge, pivotal), cartilages, muscles, locomotion in earthworm, snail, bird, fish.',
                                'subtopics' => [
                                    'Types of Synovial Joints',
                                    'Human Bone Framework',
                                    'Muscle Contraction Pairs',
                                    'Gait of Animals',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'The Living Organisms — Characteristics and Habitats',
                                'description' => 'Terrestrial and aquatic habitats, adaptations in camel, fish, cactus; abiotic components; characteristics of life.',
                                'subtopics' => [
                                    'Desert & Aquatic Adaptations',
                                    'Biotic vs Abiotic Factors',
                                    'Respiration & Excretion in Living',
                                    'Response to Stimuli',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Motion and Measurement of Distances',
                                'description' => 'History of transport, SI standard units (meter), parallax error in measuring length, rectilinear, circular, periodic motion.',
                                'subtopics' => [
                                    'Standard Units (SI System)',
                                    'Correct Measurement Technique',
                                    'Rectilinear vs Circular Motion',
                                    'Periodic & Oscillatory Motion',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Light, Shadows and Reflections',
                                'description' => 'Luminous objects, shadow formation conditions, pinhole camera inversion, mirrors and rectilinear propagation.',
                                'subtopics' => [
                                    'Light Travels in Straight Line',
                                    'Pinhole Camera Construction',
                                    'Shadow Formation Mechanics',
                                    'Plane Mirror Reflection',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Electricity and Circuits',
                                'description' => 'Electric cell terminals, electric bulb filament, complete vs open circuits, switch mechanism, conductors and insulators.',
                                'subtopics' => [
                                    'Chemical Energy to Electrical',
                                    'Circuit Diagram Symbols',
                                    'Switch in Circuit Loop',
                                    'Conductors vs Insulators',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Fun with Magnets',
                                'description' => 'Discovery of lodestone, magnetic vs non-magnetic materials, magnetic poles, freely suspended needle compass, demagnetization.',
                                'subtopics' => [
                                    'Magnetic Attraction & Repulsion',
                                    'Earth as a Giant Magnet',
                                    'Making an Artificial Magnet',
                                    'Care and Storage of Magnets',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Air Around Us',
                                'description' => 'Atmospheric composition (Nitrogen, Oxygen, Carbon Dioxide), smoke and dust, oxygen cycle, aquatic and underground respiration.',
                                'subtopics' => [
                                    'Percentage Composition of Air',
                                    'Candle Glass Burning Experiment',
                                    'Respiration of Aquatic Life',
                                    'Windmills and Atmosphere Layers',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Social Science',
                        'code' => 'SST-06',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'What, Where, How and When? (Early Humans)',
                                'description' => 'Historical sources, manuscripts, inscriptions, archaeology, hunter-gatherer migrations.',
                                'subtopics' => [
                                    'Manuscripts & Inscriptions',
                                    'Narmada Valley Settlements',
                                    'Dating in History (BC/AD)',
                                    'Archaeological Excavations',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'From Hunting-Gathering to Growing Food',
                                'description' => 'Neolithic revolution, domestication of plants/animals, Mehrgarh findings, stone tools.',
                                'subtopics' => [
                                    'Palaeolithic vs Neolithic Tools',
                                    'Domestication of Crops',
                                    'Mehrgarh Archaeological Site',
                                    'Burials and Customs',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'In the Earliest Cities (Harappan Civilization)',
                                'description' => 'Indus Valley grid architecture, Great Bath of Mohenjodaro, seals, crafts, drains, trade.',
                                'subtopics' => [
                                    'Town Planning & Great Bath',
                                    'Drainage Systems of Mohenjodaro',
                                    'Harappan Seals & Weights',
                                    'Mystery of Harappan Decline',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Kingdoms, Kings and an Early Republic',
                                'description' => 'Magadha empire rise, Rajgriha, Pataliputra, Vajji gana-sangha democratic republic.',
                                'subtopics' => [
                                    '16 Mahajanapadas Map',
                                    'Forts, Armies and Taxes',
                                    'Vajji Gana System of Democracy',
                                    'Alexander Invasion Context',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'The Earth in the Solar System',
                                'description' => 'Planets, dwarf planets, satellites, constellations, asteroids, meteoroids, Earth as a unique sphere.',
                                'subtopics' => [
                                    'Eight Planets Order',
                                    'Asteroid Belt & Meteoroids',
                                    'Constellations (Saptarishi)',
                                    'Earth Position & Goldilocks Zone',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Globe: Latitudes and Longitudes',
                                'description' => 'Equator, Tropics of Cancer/Capricorn, Arctic/Antarctic circles, Prime Meridian, time zones calculation.',
                                'subtopics' => [
                                    'Parallels of Latitude',
                                    'Meridians of Longitude',
                                    'Indian Standard Time (82.5° E)',
                                    'Calculating Local Time',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Motions of the Earth (Rotation and Revolution)',
                                'description' => 'Axis tilt of 23.5°, day/night cycle, orbital revolution, summer/winter solstices, equinoxes.',
                                'subtopics' => [
                                    'Circle of Illumination',
                                    'Leap Year Math',
                                    'Summer & Winter Solstice',
                                    'Equinox Dates (21 Mar, 23 Sep)',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Major Domains of the Earth',
                                'description' => 'Lithosphere, Hydrosphere, Atmosphere layers, Biosphere life-supporting zone.',
                                'subtopics' => [
                                    'Seven Continents & Five Oceans',
                                    'Troposphere to Exosphere',
                                    'Deepest Ocean Trenches',
                                    'Biosphere Ecological Balance',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Understanding Diversity & Discrimination',
                                'description' => 'Linguistic, religious, cultural diversity in Ladakh and Kerala, stereotypes, Dr. B.R. Ambedkar struggles.',
                                'subtopics' => [
                                    'Ladakh vs Kerala Comparative Study',
                                    'Stereotypes and Prejudices',
                                    'Dr. Ambedkar Civil Rights',
                                    'Unity in Diversity Concept',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'What is Government? & Panchayati Raj',
                                'description' => 'Levels of government, universal adult franchise, Gram Sabha, Gram Panchayat, Block and Zila Parishad.',
                                'subtopics' => [
                                    'Democracy vs Monarchy',
                                    'Universal Adult Franchise',
                                    'Gram Sabha Deliberations',
                                    'Three Tiers of Panchayati Raj',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-06',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Who Did Patrick’s Homework? & A House, A Home',
                                'description' => 'Whimsical story of an elf motivating self-study, poetry on hearth and familial warmth.',
                                'subtopics' => [
                                    'Self-Help and Diligence',
                                    'House vs Home Distinction',
                                    'Nouns and Pronouns in Context',
                                    'Vocabulary Expansion',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'How the Dog Found Himself a New Master! & The Kite',
                                'description' => 'Canine evolutionary folklore, lyrical poetry on colorful kites soaring in breeze.',
                                'subtopics' => [
                                    'Evolutionary Folklore Theme',
                                    'Action Verbs in Poetry',
                                    'Subject-Verb Agreement',
                                    'Creative Descriptive Writing',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Taro’s Reward & The Quarrel',
                                'description' => 'Filial piety in Japanese folklore, sibling quarrels verse, character ethics.',
                                'subtopics' => [
                                    'Filial Devotion & Magic Spring',
                                    'Rhyme Scheme in Sibling Verse',
                                    'Adjectives & Degrees of Comparison',
                                    'Informal Letter Writing',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'An Indian-American Woman in Space: Kalpana Chawla',
                                'description' => 'Biographical tribute to astronaut Kalpana Chawla, space shuttle Columbia mission.',
                                'subtopics' => [
                                    'Kalpana Chawla Biography',
                                    'Space Mission Science',
                                    'Prepositions & Phrasal Verbs',
                                    'Notice Writing Format',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'A Different Kind of School',
                                'description' => 'Miss Beam empathy school teaching disability inclusion, kindness and service.',
                                'subtopics' => [
                                    'Disability Empathy & Inclusion',
                                    'Direct to Indirect Speech Basics',
                                    'Diary Entry Writing',
                                    'Values of Sensitivity',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Fair Play (Munshi Premchand)',
                                'description' => 'Jumman Shaikh and Algu Chowdhry panchayat justice tale, impartial judiciary.',
                                'subtopics' => [
                                    'Impartial Justice Ethics',
                                    'Panchayat System in Literature',
                                    'Active and Passive Voice Intro',
                                    'Formal Application Writing',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Computer Science',
                        'code' => 'CS-06',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Fundamentals of Computer Systems',
                                'description' => 'Input, Process, Output (IPO) cycle, hardware, system and application software, memory units.',
                                'subtopics' => [
                                    'IPO Cycle Architecture',
                                    'CPU: ALU, CU, Registers',
                                    'RAM, ROM, Cache Memory',
                                    'Storage Units (KB, MB, GB, TB)',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Operating Systems and GUI Navigation',
                                'description' => 'Role of OS, Windows and Linux file hierarchies, desktop management, shortcuts.',
                                'subtopics' => [
                                    'Functions of an Operating System',
                                    'File and Folder Management',
                                    'Windows Explorer Navigation',
                                    'Keyboard Shortcuts Mastery',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Algorithms and Flowcharts',
                                'description' => 'Step-by-step logic, sequence, decision making, loop structures, standard flowchart symbols.',
                                'subtopics' => [
                                    'Defining Computational Algorithms',
                                    'Terminal, Process, Decision Symbols',
                                    'Flowcharting Real-life Problems',
                                    'Dry Running Logic',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Introduction to Scratch Visual Programming',
                                'description' => 'Sprites, stage, motion blocks, event triggers, costume changes, broadcast messaging.',
                                'subtopics' => [
                                    'Scratch IDE Workspace',
                                    'Motion & Looks Code Blocks',
                                    'Sound & Control Loops',
                                    'Building an Interactive Game',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'HTML Basics & Web Page Formatting',
                                'description' => 'Tags, elements, attributes, headings, paragraphs, lists, links, image embedding.',
                                'subtopics' => [
                                    'HTML Structure (head, body)',
                                    'Heading Tags h1 to h6',
                                    'Ordered & Unordered Lists',
                                    'Image Tag and Hyperlinks',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Cyber Safety and Netiquette',
                                'description' => 'Password hygiene, safe browsing, digital footprints, cyberbullying awareness.',
                                'subtopics' => [
                                    'Strong Password Formulas',
                                    'Safe Search & Phishing Alerts',
                                    'Online Etiquette (Netiquette)',
                                    'Protecting Personal Information',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-06',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Vah Chidiya Jo Aur Bachpan (Krishna Sobti)',
                                'description' => 'Lyric on freedom-loving bird, nostalgia of childhood in early 20th century Shimla.',
                                'subtopics' => [
                                    'Kedarnath Agarwal Kavita',
                                    'Krishna Sobti Bachpan Sansmaran',
                                    'Bhasha Ki Baat',
                                    'Shabdaarth',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Nadan Dost (Premchand)',
                                'description' => 'Keshav and Shyama innocent bird nest curiosity, respecting wildlife habitats.',
                                'subtopics' => [
                                    'Premchand Ki Nadan Dost',
                                    'Prakriti Se Sanvedna',
                                    'Shamsher Bahadur Singh Kavita',
                                    'Visheshan Aur Kriya',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Jhansi Ki Rani (Subhadra Kumari Chauhan)',
                                'description' => 'Immortal ballad celebrating Rani Lakshmibai valor in the 1857 First War of Independence.',
                                'subtopics' => [
                                    '1857 Kranti Ka Sandarbh',
                                    'Veer Ras Kavita Pathan',
                                    'Aitihasik Mahatva',
                                    'Kavita Ki Tumbandi',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Vyakaran: Sandhi, Samas Aur Nibandh',
                                'description' => 'Vowel sandhi rules, tatpurush samas, popular idioms, structured Hindi essays.',
                                'subtopics' => [
                                    'Dirgha Aur Guna Sandhi',
                                    'Muhavaron Ka Vakya Prayog',
                                    'Vigyan Ke Chamatkar Nibandh',
                                    'Anopacharik Patra',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            7 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-07',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Integers (Multiplication & Division)',
                                'description' => 'Rules of signs, commutative/associative/distributive laws, identity and zero properties.',
                                'subtopics' => [
                                    'Multiplication of positive & negative',
                                    'Division of integers & properties',
                                    'Distributive law a(b + c)',
                                    'Word problems on temperatures',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Fractions and Decimals',
                                'description' => 'Multiplication/division of fractions, decimal arithmetic, word problems.',
                                'subtopics' => [
                                    'Fraction multiplication (of)',
                                    'Reciprocal & division of fractions',
                                    'Multiplying decimals by 10, 100',
                                    'Division of decimals by integers',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Data Handling (Mean, Median, Mode)',
                                'description' => 'Arithmetic mean, range, mode of ungrouped data, median, double bar graphs, probability basics.',
                                'subtopics' => [
                                    'Calculating Average Mean',
                                    'Mode of frequent items',
                                    'Median middle value',
                                    'Double Bar Graph comparisons',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Simple Equations',
                                'description' => 'Setting up algebraic equations, solving by transposition, balancing method.',
                                'subtopics' => [
                                    'Formulating equations from statements',
                                    'Solving by systematic balancing',
                                    'Transposition of terms',
                                    'Word problems on age & numbers',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Lines and Angles',
                                'description' => 'Complementary, supplementary, adjacent, vertically opposite angles; transversal lines & parallel lines.',
                                'subtopics' => [
                                    'Complementary (90°) & Supplementary (180°)',
                                    'Linear pair of angles',
                                    'Alternate interior & exterior angles',
                                    'Corresponding angles condition',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'The Triangle and Its Properties',
                                'description' => 'Medians, altitudes, exterior angle theorem, angle sum property (180°), Pythagoras theorem.',
                                'subtopics' => [
                                    'Median and Altitude distinction',
                                    'Exterior angle = sum of interior opp',
                                    'Angle sum property 180°',
                                    'Pythagoras theorem a² + b² = c²',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Comparing Quantities (Percentage & Simple Interest)',
                                'description' => 'Ratios to percentage, profit and loss percentage, principal, rate, time, simple interest formula.',
                                'subtopics' => [
                                    'Converting fractions to %',
                                    'Profit % and Loss % formulas',
                                    'Simple Interest SI = (P×R×T)/100',
                                    'Total Amount = Principal + SI',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Rational Numbers',
                                'description' => 'Positive/negative rationals, standard form, rationals on number line, four basic arithmetic operations.',
                                'subtopics' => [
                                    'Defining p/q form (q ≠ 0)',
                                    'Equivalent rational numbers',
                                    'Comparison of rational numbers',
                                    'Operations (+, -, ×, ÷) on rationals',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Science',
                        'code' => 'SCI-07',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Nutrition in Plants',
                                'description' => 'Autotrophic nutrition, photosynthesis equation, chlorophyll, stomata, insectivorous plants, fungi saprotrophs.',
                                'subtopics' => [
                                    '6CO2 + 6H2O → C6H12O6 + 6O2',
                                    'Stomatal guard cells & transpiration',
                                    'Heterotrophic & Parasitic plants (Cuscuta)',
                                    'Symbiosis in Lichens & Rhizobium',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Nutrition in Animals',
                                'description' => 'Human alimentary canal, peristalsis, stomach acid, liver bile, villi absorption, ruminant stomach.',
                                'subtopics' => [
                                    'Digestion from mouth to anus',
                                    'Gastric HCl & pepsin action',
                                    'Villi surface area absorption',
                                    'Ruminants: cud chewing & rumen',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Heat and Temperature',
                                'description' => 'Clinical vs laboratory thermometers, Celsius and Fahrenheit scales, conduction, convection, radiation.',
                                'subtopics' => [
                                    'Clinical thermometer kink & range',
                                    'Conduction in solids (metals)',
                                    'Convection currents in fluids/sea breeze',
                                    'Radiation through vacuum',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Acids, Bases and Salts',
                                'description' => 'Natural indicators (litmus, turmeric, China rose), neutralization reactions, antacid applications.',
                                'subtopics' => [
                                    'Acid vs Base chemical properties',
                                    'Litmus color shifts',
                                    'Neutralization: Acid + Base → Salt + Water',
                                    'Treating bee stings & soil acidity',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Physical and Chemical Changes',
                                'description' => 'Reversible vs irreversible, rusting of iron, galvanization, magnesium ribbon burning, copper sulphate crystal.',
                                'subtopics' => [
                                    'Physical change characteristics',
                                    'Chemical change signs (gas, color, heat)',
                                    'Rusting formula Fe2O3·xH2O',
                                    'Crystallization purification',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Respiration in Organisms',
                                'description' => 'Aerobic vs anaerobic respiration, human respiratory system, diaphragm movement, respiration in fish/insects.',
                                'subtopics' => [
                                    'Glucose breakdown with/without O2',
                                    'Lactic acid buildup in muscles',
                                    'Inhalation/Exhalation mechanics',
                                    'Tracheal tubes in insects, gills in fish',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Transportation in Animals and Plants',
                                'description' => 'Circulatory system, heart chambers, arteries, veins, capillaries, blood groups, xylem and phloem transport.',
                                'subtopics' => [
                                    'Four chambers of human heart',
                                    'Double circulation path',
                                    'Xylem water transport (suction pull)',
                                    'Phloem translocation of food',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Electric Current and Its Effects',
                                'description' => 'Circuit symbols, heating effect of electric current (Joule heating), electric fuse, electromagnetism.',
                                'subtopics' => [
                                    'Circuit diagram symbols',
                                    'Heating effect (Nichrome wire)',
                                    'Safety electric fuse & MCBs',
                                    'Electromagnet construction',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Social Science',
                        'code' => 'SST-07',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'New Kings and Kingdoms (7th to 12th Centuries)',
                                'description' => 'Pratiharas, Rashtrakutas, Palas, Chola dynasty administration, temples, bronze sculptures.',
                                'subtopics' => [
                                    'Tripartite struggle for Kanauj',
                                    'Chola village assemblies (Ur, Sabha)',
                                    'Brihadisvara temple architecture',
                                    'Chola lost-wax bronze idols',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'The Delhi Sultans',
                                'description' => 'Mamluk, Khalji, Tughluq dynasties, garrison towns to empire, Iqtadari system, Alauddin Khalji market reforms.',
                                'subtopics' => [
                                    'Raziya Sultan historic reign',
                                    'Alauddin Khalji price controls',
                                    'Muhammad bin Tughluq token currency',
                                    'Iqta and revenue system',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'The Mughal Empire (16th to 17th Centuries)',
                                'description' => 'Babur, Akbar, Mansabdari system, Sulh-i-kul policy of religious tolerance, revenue system of Todar Mal.',
                                'subtopics' => [
                                    'Mughal military campaigns',
                                    'Akbar Mansabdari hierarchy',
                                    'Todar Mal Zabt revenue survey',
                                    'Sulh-i-kul universal peace',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Environment and Ecosystems',
                                'description' => 'Natural environment, lithosphere, hydrosphere, atmosphere, biosphere, human-environment interactions.',
                                'subtopics' => [
                                    'Components of Environment',
                                    'Ecosystem food webs',
                                    'Human impact on ecosystems',
                                    'World Environment Day (June 5)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Inside Our Earth (Crust, Mantle, Core)',
                                'description' => 'Earth interior layers, rocks classification (igneous, sedimentary, metamorphic), rock cycle.',
                                'subtopics' => [
                                    'Crust (SIAL & SIMA)',
                                    'Mantle and Core (NIFE)',
                                    'Rock Cycle transformations',
                                    'Mineral wealth of Earth',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Our Changing Earth (Plate Tectonics & Landforms)',
                                'description' => 'Lithospheric plates, earthquakes, seismographs, volcanoes, river erosional features (waterfalls, meanders, ox-bows).',
                                'subtopics' => [
                                    'Endogenic vs Exogenic forces',
                                    'Richter scale & Seismograph',
                                    'Meanders and Ox-bow lakes',
                                    'Glacial moraines & Sand dunes',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Equality in Indian Democracy',
                                'description' => 'Article 15 of Indian Constitution, universal adult franchise, Midday Meal scheme, Civil Rights movement.',
                                'subtopics' => [
                                    'Constitutional guarantees of equality',
                                    'Midday Meal impact on enrollment',
                                    'Om Prakash Valmiki experiences',
                                    'Rosa Parks & US Civil Rights',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Role of Government in Health',
                                'description' => 'Public health care system vs private hospitals, Kerala healthcare model, Costa Rican non-military model.',
                                'subtopics' => [
                                    'Public Healthcare Centers (PHCs)',
                                    'Private health commercialization',
                                    'Kerala decentralized health planning',
                                    'Costa Rica healthcare approach',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-07',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Three Questions & The Squirrel',
                                'description' => 'Leo Tolstoy philosophical parable on living in the present, observational verse on squirrel.',
                                'subtopics' => [
                                    'Tolstoy Three Questions Wisdom',
                                    'Living in the Present Moment',
                                    'Modal Auxiliaries',
                                    'Paragraph Writing',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'A Gift of Chappals & The Rebel',
                                'description' => 'Children compassionate empathy giving beggar music teacher chappals, satirical verse on conformity.',
                                'subtopics' => [
                                    'Empathy vs Social Rules',
                                    'Satirical Irony in Verse',
                                    'Compound Sentences with Conjunctions',
                                    'Informal Letter',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Gopal and the Hilsa Fish & The Shed',
                                'description' => 'Comic graphic narrative of Gopal witty challenge, mystery verse on garden shed.',
                                'subtopics' => [
                                    'Visual Graphic Storytelling',
                                    'Wit and Self-Deprecation',
                                    'Direct to Indirect Speech',
                                    'Story Dialogue Writing',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'The Ashes That Made Trees Bloom & Chivvy',
                                'description' => 'Japanese fairy tale on kindness to animals, humorous verse on adult nagging.',
                                'subtopics' => [
                                    'Japanese Folklore Karma Theme',
                                    'Humorous Tone in Poetry',
                                    'Prefixes and Suffixes',
                                    'Notice Writing',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Quality (John Galsworthy) & Trees',
                                'description' => 'Galsworthy touching masterpiece on Gessler brothers integrity in craft, nature ode to trees.',
                                'subtopics' => [
                                    'Craftsmanship vs Commercialism',
                                    'Galsworthy Prose Style',
                                    'Transitive vs Intransitive Verbs',
                                    'Critical Essay Writing',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Expert Detectives & Mystery of the Talking Fan',
                                'description' => 'Maya and Nishad sleuthing mystery, whimsical verse on creaking electric fan.',
                                'subtopics' => [
                                    'Detective Mystery Narrative',
                                    'Personification in Poetry',
                                    'Prepositional Phrases',
                                    'Creative Crime Fiction',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Computer Science',
                        'code' => 'CS-07',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Computer Hardware and Number Systems',
                                'description' => 'Binary, octal, decimal, hexadecimal number systems, binary addition and conversions.',
                                'subtopics' => [
                                    'Binary to Decimal conversion',
                                    'Decimal to Binary conversion',
                                    'Binary addition rules',
                                    'ASCII and Unicode encoding',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Advanced Scratch and Block-Based Logic',
                                'description' => 'Variables, conditionals, nested loops, cloning sprites, making mini arcade games.',
                                'subtopics' => [
                                    'Creating Custom Variables',
                                    'If-Else Conditional Logic',
                                    'Cloning Sprites in Real Time',
                                    'Scoreboard & Game Physics',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'HTML5 Formatting and CSS Styling',
                                'description' => 'HTML tables, forms, input fields, inline CSS, font colors, styling div containers.',
                                'subtopics' => [
                                    'HTML Table (tr, td, th)',
                                    'Form Elements (text, radio, checkbox)',
                                    'CSS Color, Background, Margins',
                                    'Box Model Architecture',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Introduction to Python Programming',
                                'description' => 'Python syntax, print statement, variables, input function, basic data types (int, float, str).',
                                'subtopics' => [
                                    'Python Interactive vs Script mode',
                                    'Variables and Identifiers',
                                    'print() and input() functions',
                                    'Arithmetic Operators (+, -, *, /, //, %)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Python Conditional Structures (if-elif-else)',
                                'description' => 'Relational operators, logical operators (and, or, not), decision making in Python code.',
                                'subtopics' => [
                                    'Relational Operators (==, !=, >, <)',
                                    'Logical Operators (and, or, not)',
                                    'if-elif-else Syntax & Indentation',
                                    'Solving Math Logic in Python',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Cyber Ethics, Copyright and Digital Safety',
                                'description' => 'Plagiarism, software piracy, intellectual property rights, protecting personal identity online.',
                                'subtopics' => [
                                    'Intellectual Property Rights (IPR)',
                                    'Open Source vs Proprietary Software',
                                    'Digital Footprint & Privacy',
                                    'Cyber Laws in India (IT Act)',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-07',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Hum Panchhi Unmukt Gagan Ke Aur Dadi Ma',
                                'description' => 'Shivamangal Singh Suman freedom verse, heartfelt emotional tribute to affectionate grandmother.',
                                'subtopics' => [
                                    'Swatantrata Ka Sandesh',
                                    'Dadi Ma Ka Mamata Bhara Chitran',
                                    'Gramin Jeevan Ki Smritiyan',
                                    'Visheshan',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Himalaya Ki Betiyan Aur Kathputli',
                                'description' => 'Nagarjun personification of Himalayan rivers, Bhawani Prasad Mishra freedom verse of puppet.',
                                'subtopics' => [
                                    'Himalaya Aur Nadiyon Ka Rishta',
                                    'Kathputli Ki Mukti Ki Pukaar',
                                    'Bhasha Ki Baat',
                                    'Muhavare',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Mithaiwala (Bhagwati Prasad Bajpai)',
                                'description' => 'Touching tale of a bereaved father finding solace in bringing joy to children.',
                                'subtopics' => [
                                    'Vatsalya Ras Ka Chitran',
                                    'Vyapari Ke Roopo Me Prem',
                                    'Charitra Chitran',
                                    'Samvaad Shaili',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Rakt Aur Hamara Sharir',
                                'description' => 'Science-themed prose on RBC, WBC, platelets, hemoglobin, and blood donation.',
                                'subtopics' => [
                                    'Rakt Ke Ghatak',
                                    'Anemia Ke Karan Aur Bachav',
                                    'Raktdaan Mahadan',
                                    'Vyakaran Abhyas',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Vyakaran: Upsarg, Pratyay Aur Patra Lekhan',
                                'description' => 'Hindi prefixes, suffixes, compound words, formal and editorial letters.',
                                'subtopics' => [
                                    'Upsarg (Pra, Sam, Anu)',
                                    'Pratyay (Ta, Vat, Ika)',
                                    'Sampadak Ko Patra',
                                    'Vichar Pradhan Nibandh',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            8 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-08',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Rational Numbers',
                                'description' => 'Properties of rational numbers (closure, commutativity, associativity, distributivity), multiplicative inverse.',
                                'subtopics' => [
                                    'Closure and Commutative properties',
                                    'Associative and Distributive laws',
                                    'Additive & Multiplicative inverse',
                                    'Rational numbers between two rationals',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Linear Equations in One Variable',
                                'description' => 'Solving linear equations with variables on one side and both sides, cross-multiplication applications.',
                                'subtopics' => [
                                    'Equations with variable on one side',
                                    'Equations with variables on both sides',
                                    'Linear reduction by cross-multiplication',
                                    'Age and digit word problems',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Understanding Quadrilaterals',
                                'description' => 'Polygons, convex/concave, regular polygons, angle sum property (n-2)×180°, parallelograms, rhombus, rectangle, square.',
                                'subtopics' => [
                                    'Sum of interior/exterior angles',
                                    'Parallelogram properties (opp sides/angles)',
                                    'Rhombus, Rectangle, Square diagonals',
                                    'Trapezium and Kite properties',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Data Handling (Histograms & Probability)',
                                'description' => 'Organizing grouped frequency data, class intervals, constructing histograms, pie charts, empirical probability.',
                                'subtopics' => [
                                    'Grouped Frequency Distribution',
                                    'Constructing & Interpreting Histograms',
                                    'Pie Chart angle calculation (θ = (x/Total)×360°)',
                                    'Probability of random events P(E)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Squares and Square Roots',
                                'description' => 'Properties of square numbers, Pythagorean triplets, finding roots by prime factorization and long division method.',
                                'subtopics' => [
                                    'Patterns in square numbers',
                                    'Pythagorean triplets 2m, m²-1, m²+1',
                                    'Prime Factorization method',
                                    'Long Division square root algorithm',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Cubes and Cube Roots',
                                'description' => 'Hardy-Ramanujan numbers (1729), prime factorization method to find cube roots, patterns in cubes.',
                                'subtopics' => [
                                    'Cubes of even and odd numbers',
                                    'Hardy-Ramanujan number analysis',
                                    'Cube roots by Prime Factorization',
                                    'Estimating cube roots of large numbers',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Comparing Quantities (Compound Interest)',
                                'description' => 'Ratios, discount percentage, GST tax, compound interest formula A = P(1 + r/100)^n compounded annually and half-yearly.',
                                'subtopics' => [
                                    'Discount = Marked Price - Sale Price',
                                    'Sales Tax & GST calculations',
                                    'Compound Interest vs Simple Interest',
                                    'Compounding half-yearly and quarterly',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Algebraic Expressions and Identities',
                                'description' => 'Terms, factors, coefficients, monomials, binomials, polynomials, multiplication, standard identities.',
                                'subtopics' => [
                                    'Addition & Subtraction of Polynomials',
                                    'Multiplication of binomial by binomial',
                                    'Identity: (a + b)² = a² + 2ab + b²',
                                    'Identities: (a - b)², a² - b², (x + a)(x + b)',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Mensuration (Area and Volume)',
                                'description' => 'Area of trapezium, general quadrilateral, surface area and volume of cuboid, cube, and right circular cylinder.',
                                'subtopics' => [
                                    'Area of Trapezium = 1/2(a + b)h',
                                    'Total Surface Area of Cylinder = 2πr(r + h)',
                                    'Volume of Cylinder = πr²h',
                                    'Unit conversions (1 L = 1000 cm³)',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Exponents and Powers',
                                'description' => 'Negative exponents, laws of exponents a^m × a^n = a^(m+n), scientific notation and standard form for tiny numbers.',
                                'subtopics' => [
                                    'Negative exponent meaning a^(-m) = 1/a^m',
                                    'Laws of integral exponents',
                                    'Expressing small numbers in standard form',
                                    'Comparing astronomical and atomic sizes',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Direct and Inverse Proportions',
                                'description' => 'Direct variation (x/y = k) vs inverse variation (xy = k), unitary method in work, time, speed, and fuel problems.',
                                'subtopics' => [
                                    'Direct proportion condition and tables',
                                    'Inverse proportion condition and tables',
                                    'Speed-Time-Distance problems',
                                    'Men and Days work completion problems',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Factorisation',
                                'description' => 'Common factors method, grouping terms, factorisation using algebraic identities, division of polynomials.',
                                'subtopics' => [
                                    'Method of common factors',
                                    'Factorisation by regrouping terms',
                                    'Factorisation using identities a² - b²',
                                    'Splitting middle term: x² + (a+b)x + ab',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Introduction to Graphs',
                                'description' => 'Bar graphs, line graphs, linear graphs, Cartesian coordinates (x, y), independent and dependent variables.',
                                'subtopics' => [
                                    'Line Graph interpretation',
                                    'Cartesian coordinates on axes',
                                    'Plotting points (x, y)',
                                    'Time-Distance and Interest-Principal graphs',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Science',
                        'code' => 'SCI-08',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Crop Production and Management',
                                'description' => 'Kharif vs Rabi crops, soil preparation, ploughing, levelling, sowing, manures vs fertilizers, drip and sprinkler irrigation.',
                                'subtopics' => [
                                    'Kharif (Monsoon) vs Rabi (Winter)',
                                    'Manures vs Chemical Fertilizers',
                                    'Drip & Sprinkler modern irrigation',
                                    'Harvesting, Threshing, Granary Storage',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Microorganisms: Friend and Foe',
                                'description' => 'Bacteria, fungi, protozoa, algae, viruses, curd making (Lactobacillus), fermentation, antibiotics, vaccines, pathogens.',
                                'subtopics' => [
                                    'Fermentation & Yeast metabolism',
                                    'Penicillin & Antibiotic precautions',
                                    'Edward Jenner & Vaccine immunity',
                                    'Food preservation & Pasteurization',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Coal and Petroleum',
                                'description' => 'Exhaustible fossil fuels, carbonization process, fractional distillation of petroleum, fractions (LPG, petrol, diesel, bitumen).',
                                'subtopics' => [
                                    'Fossil fuel formation geology',
                                    'Products of Coal (Coke, Tar, Gas)',
                                    'Fractional Distillation fractions',
                                    'PCRA conservation guidelines',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Combustion and Flame',
                                'description' => 'Conditions for combustion (fuel, oxygen, ignition temperature), types of combustion, fire extinguishers, zones of candle flame.',
                                'subtopics' => [
                                    'Ignition temperature & inflammable substances',
                                    'CO2 vs Water fire extinguishers',
                                    'Three zones of candle flame (dark, luminous, non-luminous)',
                                    'Calorific value of fuels (kJ/kg)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Conservation of Plants and Animals',
                                'description' => 'Deforestation consequences, biosphere reserves, Pachmarhi reserve, national parks, flora and fauna, Red Data Book.',
                                'subtopics' => [
                                    'Desertification & Climate impact',
                                    'Biosphere Reserve zones (Core, Buffer)',
                                    'Endemic, Endangered & Extinct species',
                                    'Red Data Book & Project Tiger',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Reproduction in Animals',
                                'description' => 'Sexual vs asexual reproduction, male/female reproductive systems, fertilization (internal/external), IVF, binary fission, budding.',
                                'subtopics' => [
                                    'Sperm and Ovum anatomy',
                                    'Zygote, Embryo, Fetus development',
                                    'Viviparous vs Oviparous animals',
                                    'Asexual: Amoeba binary fission & Hydra budding',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Reaching the Age of Adolescence',
                                'description' => 'Puberty changes, secondary sexual characteristics, pituitary hormones, endocrine glands, sex determination (XX, XY).',
                                'subtopics' => [
                                    'Pubertal growth spurt & Adam apple',
                                    'Estrogen and Testosterone secretions',
                                    'Sex chromosomes & Genetic determination',
                                    'Nutritional requirements for adolescents',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Force and Pressure',
                                'description' => 'Contact (muscular, friction) vs non-contact forces (magnetic, electrostatic, gravitational), pressure P = F/A, atmospheric pressure.',
                                'subtopics' => [
                                    'Force as push or pull and magnitude',
                                    'Atmospheric pressure & Barometer',
                                    'Liquid pressure with depth experiment',
                                    'High pressure in needle vs blunt nail',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Friction',
                                'description' => 'Origin of friction (surface irregularities), static, sliding, rolling friction, lubricants, ball bearings, fluid drag.',
                                'subtopics' => [
                                    'Microscopic surface interlocking',
                                    'Static > Sliding > Rolling friction',
                                    'Friction as necessary evil',
                                    'Streamlining to reduce fluid drag',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Sound',
                                'description' => 'Production by vibrating bodies, propagation medium requirement, human larynx, amplitude, frequency (Hz), loudness, noise pollution.',
                                'subtopics' => [
                                    'Human vocal cords vibration',
                                    'Sound needs medium (vacuum experiment)',
                                    'Frequency, Pitch, Amplitude (Decibels)',
                                    'Audible range (20 Hz - 20,000 Hz)',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Chemical Effects of Electric Current',
                                'description' => 'Electrolytes, testing conductivity of liquids, LED tester, chemical reactions of electrolysis, electroplating copper.',
                                'subtopics' => [
                                    'Conducting liquids (salts, acids)',
                                    'Electrolysis of water (H2 and O2)',
                                    'Electroplating mechanism on cutlery',
                                    'Industrial uses of electroplating',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Some Natural Phenomena',
                                'description' => 'Electrostatic charging by friction, lightning mechanism, Benjamin Franklin kite experiment, electroscope, earthquakes.',
                                'subtopics' => [
                                    'Positive and negative charges interaction',
                                    'Gold-leaf electroscope working',
                                    'Lightning conductor protection in buildings',
                                    'Seismic waves, Focus, Epicenter, Richter scale',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Light',
                                'description' => 'Laws of reflection, regular vs diffused reflection, multiple images, kaleidoscope, periscope, human eye anatomy, eye defects.',
                                'subtopics' => [
                                    'Angle of Incidence = Angle of Reflection',
                                    'Number of images formula n = (360/θ) - 1',
                                    'Retina, Cornea, Iris, Pupil, Ciliary muscles',
                                    'Myopia, Hypermetropia, Braille system',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Social Science',
                        'code' => 'SST-08',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'How, When and Where? (Periodisation of History)',
                                'description' => 'James Mill colonial periodisation (Hindu, Muslim, British), colonial records, official archives, surveys.',
                                'subtopics' => [
                                    'Critique of James Mill periodisation',
                                    'British colonial archives & National Archives',
                                    'Survey culture in colonial administration',
                                    'Limitations of official records',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'From Trade to Territory (The East India Company)',
                                'description' => 'Battle of Plassey (1757), Battle of Buxar, Subsidiary Alliance, Doctrine of Lapse, annexation of kingdoms.',
                                'subtopics' => [
                                    'Robert Clive & Siraj-ud-Daulah',
                                    'Subsidiary Alliance by Lord Wellesley',
                                    'Tipu Sultan resistance in Mysore',
                                    'Doctrine of Lapse by Lord Dalhousie',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Ruling the Countryside (Colonial Revenue Systems)',
                                'description' => 'Permanent Settlement of Bengal (1793), Ryotwari system in South, Mahalwari in North-West, Indigo rebellion (Blue Mutiny).',
                                'subtopics' => [
                                    'Permanent Settlement zamindari terms',
                                    'Ryotwari system of Thomas Munro',
                                    'Mahalwari system of Holt Mackenzie',
                                    'Blue Mutiny (1859) against planters',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'When People Rebel: 1857 and After',
                                'description' => 'Causes of 1857 revolt (greased cartridges, loss of sovereignty), Mangal Pandey, Bahadur Shah Zafar, post-1857 Queen Proclamation.',
                                'subtopics' => [
                                    'Greased cartridge mutiny trigger',
                                    'Mangal Pandey at Barrackpore',
                                    'Revolt centers (Delhi, Kanpur, Lucknow, Jhansi)',
                                    'Government of India Act 1858 reforms',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Resources (Types and Conservation)',
                                'description' => 'Natural, human-made, human resources; actual vs potential; renewable vs non-renewable; sustainable development principles.',
                                'subtopics' => [
                                    'Classification of resources',
                                    'Renewable vs Non-renewable limits',
                                    'Sustainable Development principles',
                                    'Resource planning & conservation',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Land, Soil, Water, Natural Vegetation and Wildlife',
                                'description' => 'Land use patterns, soil profile horizons, soil erosion mitigation (contour ploughing, terrace farming), CITES convention.',
                                'subtopics' => [
                                    'Soil profile horizons (Topsoil, Subsoil, Bedrock)',
                                    'Soil conservation: Mulching, Shelter belts',
                                    'Water scarcity & rainwater harvesting',
                                    'CITES protection of endangered flora/fauna',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'The Indian Constitution & Secularism',
                                'description' => 'Key features of Indian Constitution (federalism, parliamentary government, separation of powers, fundamental rights, secularism).',
                                'subtopics' => [
                                    'Constituent Assembly & Dr. B.R. Ambedkar',
                                    'Six Fundamental Rights (Articles 14-32)',
                                    'Separation of Powers (Legislature, Executive, Judiciary)',
                                    'Indian Secularism vs American Secularism',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Parliament and the Making of Laws',
                                'description' => 'Role of Lok Sabha and Rajya Sabha, passing of a bill into law, Question Hour, Private Member bills, Domestic Violence Act 2005.',
                                'subtopics' => [
                                    'Bicameral Parliament structure',
                                    'Process of passing an Ordinary Bill',
                                    'Question Hour & parliamentary accountability',
                                    'Civil society role in Domestic Violence Act',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Judiciary in India',
                                'description' => 'Structure of courts (Supreme Court, High Courts, Subordinate courts), independence of judiciary, Public Interest Litigation (PIL).',
                                'subtopics' => [
                                    'Appellate system in Indian courts',
                                    'Judicial Review & basic structure doctrine',
                                    'Criminal law vs Civil law distinctions',
                                    'PIL revolutionary impact on justice',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Understanding Marginalisation & Public Facilities',
                                'description' => 'Adivasis, Muslims, Scheduled Castes marginalisation, Sachar Committee report, safe drinking water, Article 21 Right to Life.',
                                'subtopics' => [
                                    'Adivasi displacement & forest rights',
                                    'Sachar Committee socio-economic findings',
                                    'Safe drinking water as Fundamental Right',
                                    'Public transport and government duties',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language',
                        'code' => 'ENG-08',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The Best Christmas Present in the World & The Ant and the Cricket',
                                'description' => 'World War I Christmas truce letter touching humanity, Aesop fable on foresight and labor.',
                                'subtopics' => [
                                    'WWI Christmas Truce History',
                                    'Aesop Parable on Industry',
                                    'Past Perfect Tense',
                                    'Diary Entry Writing',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'The Tsunami & Geography Lesson',
                                'description' => '2004 Indian Ocean tsunami heroism stories, poetic perspective on urbanization from airplane.',
                                'subtopics' => [
                                    'Natural Disaster Survival Accounts',
                                    'Aerial Geometrical Poetry',
                                    'Active to Passive Voice',
                                    'Formal Letter to Editor',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Glimpses of the Past & Macavity: The Mystery Cat',
                                'description' => 'Pictorial history of Indian freedom struggle (1757-1857), T.S. Eliot whimsical feline masterpiece.',
                                'subtopics' => [
                                    'Freedom Struggle Graphic Narrative',
                                    'T.S. Eliot Comic Verse Craft',
                                    'Direct & Indirect Speech Rules',
                                    'Speech Writing',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Bepin Choudhury’s Lapse of Memory & The Last Bargain',
                                'description' => 'Satyajit Ray psychological mystery on retribution, Rabindranath Tagore poem on freedom from worldly bargains.',
                                'subtopics' => [
                                    'Satyajit Ray Prose Mastery',
                                    'Tagore Philosophical Freedom Poem',
                                    'Reported Speech with Modals',
                                    'Story Composition',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'The Summit Within & The School Boy',
                                'description' => 'Major H.P.S. Ahluwalia ascent of Mount Everest and internal summit, William Blake romantic anti-confinement verse.',
                                'subtopics' => [
                                    'Ahluwalia Everest Mountaineering',
                                    'Blake Romantic Nature Philosophy',
                                    'Prepositional Phrases',
                                    'Article Writing',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'This is Jody’s Fawn & The Duck and the Kangaroo',
                                'description' => 'Jody moral responsibility in rescuing orphaned fawn, Edward Lear nonsense verse on travel.',
                                'subtopics' => [
                                    'Moral Responsibility in Wildlife',
                                    'Edward Lear Limerick Rhythm',
                                    'Conditionals (If clauses)',
                                    'Email Writing Format',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'A Visit to Cambridge & When I Set Out for Lyonnesse',
                                'description' => 'Firdaus Kanga meeting with physicist Stephen Hawking on disability, Thomas Hardy poetic inspiration.',
                                'subtopics' => [
                                    'Stephen Hawking Interview',
                                    'Hardy Architectural Verse',
                                    'Conjunctions & Synthesis of Sentences',
                                    'Debate Writing',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'A Short Monsoon Diary & On the Grasshopper and Cricket',
                                'description' => 'Ruskin Bond Himalayan nature reflections during Mussoorie rains, John Keats eternal song of earth.',
                                'subtopics' => [
                                    'Ruskin Bond Nature Prose',
                                    'Keats Romantic Sonnet Analysis',
                                    'Relative Clauses (who, which, that)',
                                    'Book Review Writing',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Computer Science',
                        'code' => 'CS-08',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Computer Networks and Topologies',
                                'description' => 'LAN, MAN, WAN, PAN; Star, Ring, Bus, Mesh, Tree topologies; transmission media (twisted pair, fiber optic, Wi-Fi).',
                                'subtopics' => [
                                    'Network Classification by Scale',
                                    'Bus, Star, Ring, Mesh Topologies',
                                    'Guided vs Unguided Media',
                                    'Network devices: Hub, Switch, Router',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Database Management Systems (DBMS) & Tables',
                                'description' => 'What is a DBMS, Relational DBMS (RDBMS), primary key, foreign key, fields, records, creating tables.',
                                'subtopics' => [
                                    'Flat file vs Relational Model',
                                    'Primary Key uniqueness constraint',
                                    'Candidate and Foreign Keys',
                                    'Data types in Database schemas',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'SQL (Structured Query Language) Basics',
                                'description' => 'DDL vs DML commands, CREATE TABLE, INSERT INTO, SELECT, WHERE clause, ORDER BY, UPDATE, DELETE.',
                                'subtopics' => [
                                    'DDL vs DML command distinction',
                                    'SELECT * FROM table WHERE condition',
                                    'Filtering with AND, OR, NOT, LIKE',
                                    'Sorting with ORDER BY (ASC/DESC)',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Python Loops and Iterations (for and while)',
                                'description' => 'Range() function, for loop, while loop, break and continue statements, nested loops, computing factorials/primes.',
                                'subtopics' => [
                                    'range(start, stop, step) syntax',
                                    'for loop over sequences',
                                    'while loop condition termination',
                                    'break and continue control flow',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Python Strings and List Data Structures',
                                'description' => 'String indexing, slicing, concatenation, string methods (.upper(), .split()); List operations (.append(), .pop(), .sort()).',
                                'subtopics' => [
                                    'String slicing [start:end:step]',
                                    'String built-in methods',
                                    'List mutability and indexing',
                                    'List methods: append, extend, pop',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Cyber Threats, Malware and Information Security',
                                'description' => 'Viruses, worms, trojans, ransomware, spyware, phishing attacks, firewalls, antivirus mechanisms, two-factor authentication.',
                                'subtopics' => [
                                    'Types of Malicious Software',
                                    'Phishing & Social Engineering',
                                    'Firewalls & Encryption basics',
                                    'Two-Factor Authentication (2FA)',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Hindi Language',
                        'code' => 'HIN-08',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Dhvani Aur Lakh Ki Choodiyan',
                                'description' => 'Suryakant Tripathi Nirala optimistic youth verse, Kamtanath story on mechanized industrial loss of village artisans.',
                                'subtopics' => [
                                    'Nirala Pragatisheel Kavita',
                                    'Hastshilp Par Udyogon Ka Prabhav',
                                    'Badlu Kaka Ka Charitra',
                                    'Paryaywachi Shabd',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Bus Ki Yatra (Harishankar Parsai)',
                                'description' => 'Parsai timeless satirical masterpiece on ramshackle public transport and corporate negligence.',
                                'subtopics' => [
                                    'Vyangya Vidha Ka Parichay',
                                    'Parsai Ki Hasya-Vyangya Bhasha',
                                    'Bhartiya Yatayat Vyavastha',
                                    'Vakya Shuddhi',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Diwanon Ki Hasti Aur Chitthiyon Ki Anuthi Duniya',
                                'description' => 'Bhagwati Charan Verma bohemian patriotism verse, cultural history and warmth of hand-written letters.',
                                'subtopics' => [
                                    'Deshbhakti Aur Mastmaula Swabhav',
                                    'Patra Lekhan Ka Aitihasik Vikas',
                                    'Dak Sewa Ki Prasangikta',
                                    'Muhavare',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Bhagwan Ke Dakiye (Ramdhari Singh Dinkar)',
                                'description' => 'Dinkar visionary poem portraying birds and clouds as messengers of universal brotherhood across nations.',
                                'subtopics' => [
                                    'Vishwa Bandhutva Ka Sandesh',
                                    'Prakriti Me Seemaon Ka Abhav',
                                    'Kavita Ka Bhaav Soundarya',
                                    'Shabdaarth',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Sudama Charit (Narottamdas)',
                                'description' => 'Classic Braj Bhasha poetic depiction of Shri Krishna deep, unconditioned friendship with impoverished Sudama.',
                                'subtopics' => [
                                    'Braj Bhasha Pad Rachna',
                                    'Sache Mitra Ki Pehchan',
                                    'Atithi Satkar Aur Karuna',
                                    'Alankar Parichay',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Vyakaran: Sandhi, Samas, Ras Aur Nibandh Lekhan',
                                'description' => 'Advanced Hindi sandhi, six samas categories, introductory Ras in poetry, formal editorial letters.',
                                'subtopics' => [
                                    'Vyanjan Aur Visarga Sandhi',
                                    'Samas Ke 6 Bhed',
                                    'Nav Ras Parichay',
                                    'Samasamyik Vishay Par Nibandh',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            9 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-09',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Number Systems',
                                'description' => 'Irrational numbers on number line, real numbers and decimal expansions, rationalising the denominator, laws of exponents.',
                                'subtopics' => [
                                    'Representation of √x on number line',
                                    'Terminating vs non-terminating recurring decimals',
                                    'Rationalisation of 1/(a + b√x)',
                                    'Laws of real exponents',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Polynomials',
                                'description' => 'Degree of polynomial, zeros of polynomial, Remainder Theorem, Factor Theorem, factorising quadratic and cubic polynomials.',
                                'subtopics' => [
                                    'Factor Theorem proofs & applications',
                                    'Splitting the middle term method',
                                    'Factorising cubic polynomials by trial & error',
                                    'Identities: (x+y+z)², x³+y³+z³-3xyz',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Coordinate Geometry',
                                'description' => 'Cartesian plane, coordinate axes, quadrants, plotting points, abscissa and ordinate.',
                                'subtopics' => [
                                    'Origin and Four Quadrants',
                                    'Abscissa (x) and Ordinate (y)',
                                    'Plotting points in Cartesian plane',
                                    'Graphing geometric shapes',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Linear Equations in Two Variables',
                                'description' => 'Standard form ax + by + c = 0, infinitely many solutions, graphing linear equations, equations parallel to axes.',
                                'subtopics' => [
                                    'Standard form ax + by + c = 0',
                                    'Finding solution pairs (x, y)',
                                    'Graph of linear equations',
                                    'Equations of lines parallel to axes (x = a, y = b)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Introduction to Euclid’s Geometry',
                                'description' => 'Euclid axioms and five postulates, equivalent versions of Euclid fifth postulate.',
                                'subtopics' => [
                                    'Seven axioms of Euclid',
                                    'Five geometric postulates',
                                    'Parallel postulate analysis',
                                    'Logical deductive proofs',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Lines and Angles',
                                'description' => 'Intersecting and parallel lines, linear pair axiom, vertically opposite angles theorem, parallel lines with transversal, angle sum of triangle.',
                                'subtopics' => [
                                    'Linear pair theorem proof',
                                    'Vertically opposite angles equality',
                                    'Alternate & Interior angle theorems',
                                    'Angle sum of triangle = 180° theorem',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Triangles (Congruence)',
                                'description' => 'Criteria for congruence (SAS, ASA, AAS, SSS, RHS), properties of isosceles triangles, inequalities in a triangle.',
                                'subtopics' => [
                                    'SAS, ASA, AAS Congruence criteria',
                                    'Angles opposite equal sides are equal',
                                    'SSS and RHS Congruence theorems',
                                    'Triangle inequality theorem',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Quadrilaterals',
                                'description' => 'Angle sum property of quadrilateral, properties of parallelogram, Mid-point theorem and its converse.',
                                'subtopics' => [
                                    'Diagonal divides parallelogram into 2 congruent triangles',
                                    'Opposite sides & angles in parallelogram',
                                    'Mid-point Theorem proof',
                                    'Converse of Mid-point Theorem',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Circles',
                                'description' => 'Equal chords and distances from center, angle subtended by an arc at the center, cyclic quadrilaterals theorem.',
                                'subtopics' => [
                                    'Chords equidistant from center',
                                    'Angle at center is double angle at circumference',
                                    'Angles in same segment are equal',
                                    'Cyclic quadrilateral opposite angles sum 180°',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Heron’s Formula',
                                'description' => 'Area of triangle using semi-perimeter s = (a+b+c)/2, Area = √[s(s-a)(s-b)(s-c)], applications to quadrilaterals.',
                                'subtopics' => [
                                    'Heron formula derivation & structure',
                                    'Area of scalene and isosceles triangles',
                                    'Dividing quadrilaterals into triangles',
                                    'Real-world field area problems',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Surface Areas and Volumes',
                                'description' => 'Surface areas and volumes of right circular cones and spheres/hemispheres.',
                                'subtopics' => [
                                    'Slant height of cone l = √(r² + h²)',
                                    'Curved & Total surface area of cone',
                                    'Surface area of sphere 4πr²',
                                    'Volume of cone (1/3)πr²h & sphere (4/3)πr³',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Statistics',
                                'description' => 'Bar graphs, histograms of varying base widths, frequency polygons, measures of central tendency.',
                                'subtopics' => [
                                    'Histograms with varying class widths',
                                    'Frequency polygon construction',
                                    'Arithmetic mean of grouped data',
                                    'Median and Mode calculations',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Science',
                        'code' => 'SCI-09',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Matter in Our Surroundings',
                                'description' => 'Particulate nature of matter, characteristics of particles, states of matter, latent heat of fusion and vaporisation, evaporation.',
                                'subtopics' => [
                                    'Diffusion & kinetic energy of particles',
                                    'Solid, liquid, gas comparison',
                                    'Latent heat of fusion and vaporisation',
                                    'Factors affecting rate of evaporation',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Is Matter Around Us Pure?',
                                'description' => 'Mixtures, solutions, suspensions, colloids, Tyndall effect, separation of mixtures, physical and chemical changes.',
                                'subtopics' => [
                                    'Homogeneous vs Heterogeneous mixtures',
                                    'Concentration of solutions (mass %)',
                                    'Tyndall scattering in colloids',
                                    'Elements, Compounds, and Mixtures',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Atoms and Molecules',
                                'description' => 'Laws of chemical combination (conservation of mass, constant proportions), Dalton atomic theory, valency, molecular mass, mole concept.',
                                'subtopics' => [
                                    'Law of Conservation of Mass',
                                    'Law of Constant Proportions',
                                    'Writing chemical formulas using valency',
                                    'Molecular mass and formula unit mass',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Structure of the Atom',
                                'description' => 'Charged particles, Thomson model, Rutherford alpha particle scattering experiment and planetary model, Bohr model, electronic distribution, isotopes.',
                                'subtopics' => [
                                    'Rutherford gold foil experiment',
                                    'Bohr atomic energy levels',
                                    'Valence electrons and Octet rule',
                                    'Isotopes and Isobars applications',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'The Fundamental Unit of Life (Cell Biology)',
                                'description' => 'Plasma membrane, diffusion and osmosis, nucleus, cytoplasm, cell organelles (ER, Golgi, lysosomes, mitochondria, plastids, vacuoles).',
                                'subtopics' => [
                                    'Hypotonic, Isotonic, Hypertonic osmosis',
                                    'Prokaryotic vs Eukaryotic cells',
                                    'Mitochondria ATP powerhouse',
                                    'Chloroplasts and Lysosome suicide bags',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Tissues',
                                'description' => 'Meristematic tissues (apical, intercalary, lateral), permanent plant tissues (parenchyma, collenchyma, sclerenchyma, xylem, phloem), animal tissues.',
                                'subtopics' => [
                                    'Meristematic vs Permanent tissues',
                                    'Xylem and Phloem complex vascular',
                                    'Epithelial, Connective (blood, bone, cartilage)',
                                    'Muscular and Nervous tissues (Neurons)',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Motion (Kinematics)',
                                'description' => 'Distance vs displacement, speed and velocity, uniform and non-uniform motion, acceleration, graphical derivation of equations of motion.',
                                'subtopics' => [
                                    'Distance vs Displacement vectors',
                                    'Velocity-Time & Distance-Time graphs',
                                    'Derivation of v = u + at, s = ut + 1/2at²',
                                    'Derivation of v² = u² + 2as, circular motion',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Force and Laws of Motion',
                                'description' => 'Balanced and unbalanced forces, Newton first law and inertia, Newton second law (F = ma) and momentum, Newton third law, conservation of momentum.',
                                'subtopics' => [
                                    'Inertia and Mass relationship',
                                    'Newton second law derivation F = dp/dt',
                                    'Action and reaction force pairs',
                                    'Law of Conservation of Momentum',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Gravitation',
                                'description' => 'Universal law of gravitation, gravitational constant G, acceleration due to gravity g, free fall, mass vs weight, thrust and pressure, Archimedes principle.',
                                'subtopics' => [
                                    'F = G(m1·m2)/r² universal law',
                                    'Variation of g with altitude and depth',
                                    'Free fall equations of motion',
                                    'Archimedes principle & Relative density',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Work and Energy',
                                'description' => 'Scientific concept of work W = F·s·cosθ, kinetic energy (1/2mv²), potential energy (mgh), law of conservation of energy, power (Watts).',
                                'subtopics' => [
                                    'Work done formula and Joules',
                                    'Derivation of Kinetic Energy KE = 1/2mv²',
                                    'Gravitational Potential Energy PE = mgh',
                                    'Law of Conservation of Mechanical Energy',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Sound (Acoustics)',
                                'description' => 'Longitudinal sound waves, compression and rarefaction, wavelength, frequency, time period, amplitude, speed of sound, echo, reverberation, ultrasound.',
                                'subtopics' => [
                                    'Longitudinal nature of sound waves',
                                    'Wave equation v = f·λ',
                                    'Echo calculation d = (v·t)/2',
                                    'Ultrasound and SONAR applications',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Improvement in Food Resources',
                                'description' => 'Crop variety improvement, hybridization, crop production management, nutrients, animal husbandry, poultry, fish farming, apiculture.',
                                'subtopics' => [
                                    'Macronutrients and Micronutrients',
                                    'Composite fish culture system',
                                    'Poultry breeds & egg production',
                                    'Organic farming and biofertilizers',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Social Science',
                        'code' => 'SST-09',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The French Revolution',
                                'description' => 'Three Estates society, storming of Bastille, Declaration of the Rights of Man, Reign of Terror (Robespierre), rise of Napoleon.',
                                'subtopics' => [
                                    'Old Regime social structure',
                                    'Tennis Court Oath & Bastille',
                                    'Robespierre Jacobin Reign of Terror',
                                    'Abolition of slavery in French colonies',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Socialism in Europe and the Russian Revolution',
                                'description' => 'Liberals, radicals, conservatives, Karl Marx socialism, 1905 Bloody Sunday, February & October 1917 Revolutions (Lenin & Bolsheviks).',
                                'subtopics' => [
                                    'Marxist dialectical materialism',
                                    'Tsar Nicholas II autocracy',
                                    'Vladimir Lenin April Theses',
                                    'Stalin collectivization of agriculture',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Nazism and the Rise of Hitler',
                                'description' => 'Weimar Republic weaknesses, Great Depression impact, Nazi propaganda, Nuremberg Laws, the Holocaust.',
                                'subtopics' => [
                                    'Treaty of Versailles grievances',
                                    'Hitler oratorical rise to power',
                                    'Destruction of democracy & Enabling Act',
                                    'Ghettos and the Holocaust genocide',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'India: Size and Location',
                                'description' => 'Latitudinal and longitudinal extent of India, Tropic of Cancer, Indian Standard Meridian (82°30’ E), strategic maritime location.',
                                'subtopics' => [
                                    'Latitudinal extent 8°4’ N to 37°6’ N',
                                    'Standard Meridian 82°30’ E at Mirzapur',
                                    'Strategic Indian Ocean trade routes',
                                    'Neighboring nations and boundaries',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Physical Features of India',
                                'description' => 'Plate tectonics, Northern Mountains (Himalayas), Northern Plains, Peninsular Plateau, Indian Desert, Coastal Plains, Islands.',
                                'subtopics' => [
                                    'Three Himalayan parallel ranges (Himadri, Himachal, Shiwalik)',
                                    'Bhabar, Terai, Bhangar, Khadar plains',
                                    'Central Highlands and Deccan Plateau',
                                    'Western Ghats vs Eastern Ghats',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Drainage (River Systems of India)',
                                'description' => 'Himalayan rivers (Indus, Ganga, Brahmaputra) vs Peninsular rivers (Narmada, Tapi, Godavari, Krishna, Kaveri), river pollution.',
                                'subtopics' => [
                                    'Indus Water Treaty & tributaries',
                                    'Ganga river system & Sundarban delta',
                                    'Brahmaputra gorge & shifting channels',
                                    'National River Conservation Plan (NRCP)',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'What is Democracy? Why Democracy?',
                                'description' => 'Definition of democracy, major features (free and fair elections, one person one vote, rule of law), arguments for and against.',
                                'subtopics' => [
                                    'Case studies: Pakistan, China, Mexico, Zimbabwe',
                                    'Key features of democratic governance',
                                    'Accountability, correction of mistakes',
                                    'Broader meanings of democracy',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Constitutional Design',
                                'description' => 'Democratic Constitution in South Africa (apartheid struggle, Nelson Mandela), making of the Indian Constitution, Preamble values.',
                                'subtopics' => [
                                    'Nelson Mandela 27-year imprisonment',
                                    'Drafting of South African non-racial charter',
                                    'Indian Constituent Assembly debates',
                                    'Preamble philosophy (Sovereign, Socialist, Secular, Democratic, Republic)',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Electoral Politics',
                                'description' => 'Why elections, voter turnout, reserved constituencies, election campaign rules, Model Code of Conduct, Election Commission of India.',
                                'subtopics' => [
                                    'Voters list & universal franchise',
                                    'Reservation for SC, ST, women',
                                    'Election Commission constitutional independence',
                                    'Free and fair election audits',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Poverty as a Challenge',
                                'description' => 'Two dimensions of poverty, poverty line estimation (calories and income), vulnerable groups, anti-poverty measures (MGNREGA).',
                                'subtopics' => [
                                    'Poverty line criteria in India (2400/2100 calories)',
                                    'Social exclusion and vulnerability',
                                    'Inter-state disparities in poverty',
                                    'MGNREGA 2005 100-day wage guarantee',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language & Literature',
                        'code' => 'ENG-09',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The Fun They Had & The Road Not Taken',
                                'description' => 'Isaac Asimov futuristic schooling prediction, Robert Frost iconic poetry on divergence and choice.',
                                'subtopics' => [
                                    'Futuristic tele-books and mechanical teachers',
                                    'Frost dilemma of diverging paths',
                                    'Simple present and future conditionals',
                                    'Analytical paragraph writing',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'The Sound of Music & Wind',
                                'description' => 'Evelyn Glennie triumph over profound deafness, Subramania Bharati resilience anthem to wind.',
                                'subtopics' => [
                                    'Sensory percussion mastery without hearing',
                                    'Metaphor of adversity in wind',
                                    'Adverbial clauses of reason',
                                    'Formal letter of complaint',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'The Little Girl & Rain on the Roof',
                                'description' => 'Katherine Mansfield story of Kezia discovering father underlying tenderness, evocative rain lyric.',
                                'subtopics' => [
                                    'Father-daughter psychological dynamic',
                                    'Sensory auditory imagery in rain',
                                    'Active and passive voice in prose',
                                    'Descriptive essay writing',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'A Truly Beautiful Mind & The Lake Isle of Innisfree',
                                'description' => 'Albert Einstein humanitarian and scientific life story, W.B. Yeats longing for peace in Innisfree.',
                                'subtopics' => [
                                    'Einstein pacifism and relativity',
                                    'Yeats lyrical pastoral escapism',
                                    'Reported speech with questions',
                                    'Diary entry format',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'My Childhood (Dr. A.P.J. Abdul Kalam) & No Men Are Foreign',
                                'description' => 'Dr. Kalam communal harmony upbringing in Rameswaram, James Kirkup universal brotherhood verse.',
                                'subtopics' => [
                                    'Kalam childhood mentors and values',
                                    'Universal human oneness poem',
                                    'Prefixes and suffixes derivation',
                                    'Article writing for school magazine',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Reach for the Top (Santosh Yadav & Maria Sharapova)',
                                'description' => 'Santosh Yadav dual Everest ascent, Maria Sharapova relentless journey to Wimbledon championship.',
                                'subtopics' => [
                                    'Defying societal gender restrictions',
                                    'Mental toughness and athletic sacrifice',
                                    'Modals expressing obligation and ability',
                                    'Speech composition',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Information Technology',
                        'code' => 'IT-09',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Introduction to IT & ITeS Industry',
                                'description' => 'Information Technology enabled Services (ITeS), BPO, BPM, IT applications in medicine, banking, governance.',
                                'subtopics' => [
                                    'IT vs ITeS definitions',
                                    'BPO and Call Center infrastructure',
                                    'E-Governance & Digital India portals',
                                    'IT in engineering and medicine',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Data Entry and Keyboarding Skills',
                                'description' => 'Touch typing method, ergonomic sitting posture, Rapid Typing tutor metrics (WPM, accuracy percentage).',
                                'subtopics' => [
                                    'Home row keys positioning',
                                    'Ergonomic seating and monitor distance',
                                    'Words Per Minute (WPM) calculations',
                                    'Accuracy and error penalty index',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Digital Documentation (Word Processing)',
                                'description' => 'LibreOffice Writer / MS Word: paragraph styles, tables, find-and-replace, mail merge automation.',
                                'subtopics' => [
                                    'Paragraph indentations and line spacing',
                                    'Creating & formatting complex tables',
                                    'Headers, Footers, and Page Breaks',
                                    'Mail Merge step-by-step setup',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Electronic Spreadsheet (Data Analysis)',
                                'description' => 'Formulas, cell references (relative, absolute, mixed), mathematical functions (SUM, AVERAGE, IF), charts.',
                                'subtopics' => [
                                    'Grid layout & cell naming',
                                    'Relative vs Absolute reference ($A$1)',
                                    'SUM, AVERAGE, COUNT, MAX, MIN, IF',
                                    'Bar, Column, and Pie Chart generation',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Digital Presentation',
                                'description' => 'Slide transitions, animations, master slide templates, inserting multimedia audio/video, rehearsal timings.',
                                'subtopics' => [
                                    'Principles of effective slide design',
                                    'Master slide custom templates',
                                    'Slide transitions vs Object animations',
                                    'Rehearsing presentation delivery',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Cyber Safety, Digital Ethics and Green Computing',
                                'description' => 'Phishing defense, malware types, e-waste hazards, sustainable disposal, cyber etiquette.',
                                'subtopics' => [
                                    'Recognizing email phishing attempts',
                                    'E-waste toxic components (lead, mercury)',
                                    'Green computing energy conservation',
                                    'Cyber reporting mechanisms in India',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            10 => [
                'subjects' => [
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-10',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Real Numbers',
                                'description' => 'Fundamental Theorem of Arithmetic, proving irrationality of √2, √3, √5, prime factorisation.',
                                'subtopics' => [
                                    'Fundamental Theorem of Arithmetic',
                                    'Proving irrationality by contradiction',
                                    'Decimal expansions of rational numbers',
                                    'HCF and LCM relation HCF×LCM = a×b',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Polynomials',
                                'description' => 'Geometrical meaning of zeros, relationship between zeros and coefficients of quadratic polynomials.',
                                'subtopics' => [
                                    'Geometrical meaning of zeros (x-intercepts)',
                                    'Sum of zeros α + β = -b/a',
                                    'Product of zeros αβ = c/a',
                                    'Forming quadratic polynomial k[x² - (α+β)x + αβ]',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Pair of Linear Equations in Two Variables',
                                'description' => 'Graphical method, algebraic methods (substitution, elimination), consistency conditions (a1/a2 vs b1/b2).',
                                'subtopics' => [
                                    'Intersecting, parallel, coincident conditions',
                                    'Method of Substitution',
                                    'Method of Elimination',
                                    'Word problems on speed, upstream/downstream',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Quadratic Equations',
                                'description' => 'Standard form ax² + bx + c = 0, solution by factorisation and quadratic formula, nature of roots (discriminant D = b² - 4ac).',
                                'subtopics' => [
                                    'Quadratic formula x = (-b ± √D)/2a',
                                    'Discriminant D > 0, D = 0, D < 0 nature',
                                    'Real-world speed and pipe work problems',
                                    'Solving by factorisation',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Arithmetic Progressions (AP)',
                                'description' => 'nth term of an AP (an = a + (n-1)d), sum of first n terms (Sn = n/2[2a + (n-1)d]), practical applications.',
                                'subtopics' => [
                                    'Common difference d and general term an',
                                    'Finding unknown terms in AP',
                                    'Sum of first n terms formula Sn',
                                    'Word problems on installment savings',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Triangles (Similarity)',
                                'description' => 'Basic Proportionality Theorem (Thales theorem) and its converse, criteria for similarity (AAA, SSS, SAS).',
                                'subtopics' => [
                                    'BPT (Thales Theorem) formal proof',
                                    'Converse of BPT proof',
                                    'AAA, SSS, SAS Similarity criteria',
                                    'Shadow and height similarity calculations',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Coordinate Geometry',
                                'description' => 'Distance formula d = √[(x2-x1)² + (y2-y1)²], Section formula for internal division, mid-point formula.',
                                'subtopics' => [
                                    'Distance formula derivation',
                                    'Collinearity and geometric shapes proof',
                                    'Section formula [ (mx2+nx1)/(m+n), (my2+ny1)/(m+n) ]',
                                    'Mid-point formula and centroid of triangle',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Introduction to Trigonometry',
                                'description' => 'Trigonometric ratios of acute angles (sin, cos, tan, cosec, sec, cot), values at 0°, 30°, 45°, 60°, 90°, identity sin²θ + cos²θ = 1.',
                                'subtopics' => [
                                    'Trig ratios in right-angled triangle',
                                    'Trig values table (0° to 90°)',
                                    'Identity: sin²θ + cos²θ = 1',
                                    'Identities: 1 + tan²θ = sec²θ, 1 + cot²θ = cosec²θ',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Some Applications of Trigonometry (Heights and Distances)',
                                'description' => 'Line of sight, angle of elevation, angle of depression, solving problems with two triangles (30°, 45°, 60°).',
                                'subtopics' => [
                                    'Line of sight and horizontal reference',
                                    'Angle of Elevation vs Depression',
                                    'Single right-triangle calculations',
                                    'Double right-triangle tower/cloud problems',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Circles',
                                'description' => 'Tangent to a circle at point of contact is perpendicular to radius, lengths of tangents from external point are equal.',
                                'subtopics' => [
                                    'Tangent perpendicular to radius proof',
                                    'Tangents from external point equality proof',
                                    'Concentric circles problems',
                                    'Quadrilateral circumscribing a circle',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Areas Related to Circles',
                                'description' => 'Area of sector of angle θ (θ/360 × πr²), area of segment of circle, length of an arc (θ/360 × 2πr).',
                                'subtopics' => [
                                    'Length of arc formula',
                                    'Area of minor and major sector',
                                    'Area of minor segment = Area of sector - Area of triangle',
                                    'Designs on handkerchiefs and table covers',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Surface Areas and Volumes',
                                'description' => 'Surface area and volume of combinations of solids (cuboid, cone, cylinder, hemisphere, sphere).',
                                'subtopics' => [
                                    'Total surface area of combined solid',
                                    'Volume of combined solid (toy, test tube)',
                                    'Melting and recasting solids conservation',
                                    'Real-world water tanker capacities',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Statistics',
                                'description' => 'Mean of grouped data (Direct method, Assumed Mean method), Mode of grouped data, Median of grouped data.',
                                'subtopics' => [
                                    'Direct and Assumed Mean (d_i = x_i - a)',
                                    'Mode formula: l + [ (f1-f0)/(2f1-f0-f2) ] × h',
                                    'Median formula: l + [ (n/2 - cf)/f ] × h',
                                    'Empirical relation: 3 Median = Mode + 2 Mean',
                                ],
                            ],
                            [
                                'number' => 14,
                                'title' => 'Probability',
                                'description' => 'Theoretical probability P(E) = Number of favorable outcomes / Total outcomes, complementary events P(E) + P(not E) = 1.',
                                'subtopics' => [
                                    'Equally likely outcomes definition',
                                    'Coins, Dice, and 52 Playing Cards experiments',
                                    'Sure event (P=1) and Impossible event (P=0)',
                                    'Complementary events identity',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Science',
                        'code' => 'SCI-10',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Chemical Reactions and Equations',
                                'description' => 'Balancing chemical equations, combination, decomposition, displacement, double displacement, precipitation, redox reactions, corrosion, rancidity.',
                                'subtopics' => [
                                    'Balancing mass in chemical equations',
                                    'Exothermic vs Endothermic reactions',
                                    'Thermal and Electrolytic decomposition',
                                    'Oxidation, Reduction, Oxidising and Reducing agents',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Acids, Bases and Salts',
                                'description' => 'Chemical properties, reaction with metals, carbonates, pH scale, role of pH in daily life, preparation of Bleaching Powder, Baking Soda, Washing Soda, Plaster of Paris.',
                                'subtopics' => [
                                    'pH scale definition -log[H+]',
                                    'Acid rain and tooth decay pH thresholds',
                                    'Chlor-alkali process for NaOH and Cl2',
                                    'Plaster of Paris CaSO4·1/2H2O reaction with water',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Metals and Non-Metals',
                                'description' => 'Physical and chemical properties, reactivity series, ionic bond formation and properties, metallurgy (calcination, roasting, electrolytic refining), corrosion prevention.',
                                'subtopics' => [
                                    'Reactivity series mnemonic and reactions',
                                    'Ionic compound formation (NaCl, MgCl2)',
                                    'High melting point and conductivity of ionic salts',
                                    'Roasting vs Calcination metallurgical processes',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Carbon and Its Compounds',
                                'description' => 'Covalent bonding, tetravalency and catenation, saturated and unsaturated hydrocarbons, homologous series, functional groups, combustion, saponification.',
                                'subtopics' => [
                                    'Catenation and Tetravalency uniqueness',
                                    'Homologous series characteristics',
                                    'Reactions of Ethanol and Ethanoic acid',
                                    'Micelle formation in Soaps and Detergents',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Life Processes',
                                'description' => 'Nutrition (autotrophic & heterotrophic), human digestive system, respiration (aerobic & anaerobic), human circulatory system, double circulation, human excretory system (nephron).',
                                'subtopics' => [
                                    'Chloroplast photosynthesis light reactions',
                                    'Human alimentary canal enzymes',
                                    'Heart four chambers and double circulation',
                                    'Nephron structure and urine formation',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Control and Coordination',
                                'description' => 'Nervous system, structure of neuron, neuromuscular junction, reflex arc, parts of human brain (forebrain, midbrain, hindbrain), plant hormones, endocrine hormones.',
                                'subtopics' => [
                                    'Neuron action potential transmission',
                                    'Reflex arc sensory-motor pathway',
                                    'Cerebrum, Cerebellum, Medulla functions',
                                    'Auxins, Gibberellins, Cytokinins, Abscisic acid',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'How do Organisms Reproduce?',
                                'description' => 'DNA copying, fission, fragmentation, regeneration, budding, vegetative propagation, spore formation, sexual reproduction in flowering plants, human male/female reproductive systems.',
                                'subtopics' => [
                                    'Importance of DNA variation',
                                    'Double fertilization in angiosperm flowers',
                                    'Male (testes, vas deferens) and Female anatomy',
                                    'Contraceptive methods and reproductive hygiene',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Heredity',
                                'description' => 'Mendel laws of inheritance, monohybrid cross (3:1 ratio), dihybrid cross (9:3:3:1 ratio), sex determination in human beings (XX and XY).',
                                'subtopics' => [
                                    'Mendel pea plant selection advantages',
                                    'Monohybrid cross phenotypic vs genotypic',
                                    'Dihybrid cross independent assortment',
                                    'Sex chromosome determination in humans',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Light: Reflection and Refraction',
                                'description' => 'Spherical mirrors (concave & convex), mirror formula 1/f = 1/v + 1/u, magnification, Snell law of refraction, lens formula 1/f = 1/v - 1/u, power of lens (P = 1/f).',
                                'subtopics' => [
                                    'Sign convention rules for spherical mirrors',
                                    'Ray diagrams for concave and convex mirrors',
                                    'Refractive index n = c/v and Snell Law',
                                    'Lens formula and Power in Diopters',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'The Human Eye and the Colorful World',
                                'description' => 'Human eye structure, power of accommodation, myopia, hypermetropia, presbyopia, refraction through prism, dispersion of white light, atmospheric refraction, scattering.',
                                'subtopics' => [
                                    'Myopia and Hypermetropia ray diagram corrections',
                                    'Dispersion of white light by glass prism (VIBGYOR)',
                                    'Atmospheric refraction (twinkling of stars)',
                                    'Tyndall effect and blue color of sky',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Electricity',
                                'description' => 'Electric current, potential difference (V = W/Q), Ohm law (V = IR), factors affecting resistance, resistivity, series and parallel circuits, Joule law of heating, electric power.',
                                'subtopics' => [
                                    'Ohm Law verification graph',
                                    'Resistance formula R = ρ(l/A)',
                                    'Equivalent resistance: Series (Rs = R1+R2) vs Parallel',
                                    'Joule heating H = I²Rt and Commercial unit (kWh)',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Magnetic Effects of Electric Current',
                                'description' => 'Magnetic field lines, field due to current-carrying straight wire, circular loop, and solenoid, Fleming left-hand rule, electric motor, electromagnetic induction, Fleming right-hand rule.',
                                'subtopics' => [
                                    'Properties of magnetic field lines',
                                    'Right-Hand Thumb Rule',
                                    'Magnetic field inside a current solenoid',
                                    'Domestic electric circuits, Earthing, and Fuse',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Our Environment',
                                'description' => 'Ecosystem components, trophic levels, 10% law of energy transfer, biological magnification, ozone layer depletion (CFCs), garbage management.',
                                'subtopics' => [
                                    'Food chains and food webs',
                                    'Lindeman 10% ecological energy law',
                                    'Biomagnification of pesticide toxins',
                                    'Montreal Protocol and Ozone Chapman cycle',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Social Science',
                        'code' => 'SST-10',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The Rise of Nationalism in Europe',
                                'description' => 'Frédéric Sorrieu utopian vision, French Revolution legacy, civil code of 1804 (Napoleonic Code), unification of Italy and Germany, allegory of Marianne and Germania.',
                                'subtopics' => [
                                    'Napoleonic Code of 1804',
                                    'Zollverein customs union',
                                    'Otto von Bismarck German unification',
                                    'Giuseppe Mazzini and Count Cavour',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Nationalism in India',
                                'description' => 'First World War impact, Satyagraha concept, Rowlatt Act, Jallianwala Bagh massacre, Non-Cooperation Movement, Dandi March and Civil Disobedience, Poona Pact.',
                                'subtopics' => [
                                    'Mahatma Gandhi Champaran & Kheda satyagrahas',
                                    'Jallianwala Bagh tragedy (13 April 1919)',
                                    'Salt March to Dandi and Civil Disobedience',
                                    'Dr. Ambedkar & Gandhi Poona Pact 1932',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'The Making of a Global World',
                                'description' => 'Pre-modern world, Silk Routes, conquest and trade, Great Depression of 1929, post-war Bretton Woods institutions (IMF and World Bank).',
                                'subtopics' => [
                                    'Ancient Silk Route trade and cultural exchange',
                                    'Rinderpest cattle plague in Africa',
                                    'Causes and impact of 1929 Great Depression',
                                    'Bretton Woods twins: IMF and World Bank',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Resources and Development',
                                'description' => 'Classification of resources, sustainable development, Rio de Janeiro Earth Summit 1992, Agenda 21, land use pattern in India, soil erosion and conservation.',
                                'subtopics' => [
                                    'Resource planning steps in India',
                                    'Rio Earth Summit and Agenda 21',
                                    'Alluvial, Black, Red, Laterite soils',
                                    'Soil conservation: Strip cropping, Terrace farming',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Forest and Wildlife Resources',
                                'description' => 'Biodiversity in India, IUCN classification, Asiatic cheetah extinction, community conservation, Chipko Movement, Beej Bachao Andolan, Joint Forest Management (JFM).',
                                'subtopics' => [
                                    'IUCN endangered and vulnerable categories',
                                    'Project Tiger conservation benchmarks',
                                    'Sacred groves (Bhairavdev Dakav)',
                                    'Joint Forest Management (JFM) resolution',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Water Resources',
                                'description' => 'Water scarcity causes, multipurpose river valley projects (Bhakra Nangal, Sardar Sarovar), rainwater harvesting systems (Rooftop, Tankas, Khadins).',
                                'subtopics' => [
                                    'Dams as temples of modern India (Nehru)',
                                    'Social movements: Narmada Bachao Andolan',
                                    'Rajasthan traditional tanka systems',
                                    'Bamboo drip irrigation of Meghalaya',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Agriculture',
                                'description' => 'Types of farming (primitive subsistence, intensive subsistence, commercial), major crops (Rice, Wheat, Millets, Pulses, Tea, Coffee, Cotton), technological and institutional reforms.',
                                'subtopics' => [
                                    'Cropping seasons: Kharif, Rabi, Zaid',
                                    'Geographical conditions for Rice and Wheat',
                                    'Bhoodan-Gramdan movement of Vinoba Bhave',
                                    'Kisan Credit Card (KCC) & MSP',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Power Sharing',
                                'description' => 'Ethnic composition of Belgium and Sri Lanka, majoritarianism in Sri Lanka, accommodation in Belgium, prudential and moral reasons for power sharing.',
                                'subtopics' => [
                                    'Brussels linguistic compromise model',
                                    'Sinhala majoritarian policy consequences',
                                    'Horizontal power sharing (Organs of govt)',
                                    'Vertical power sharing (Federalism)',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Federalism',
                                'description' => 'Key features of federalism, "coming together" vs "holding together" federations, three-fold distribution of legislative powers (Union, State, Concurrent Lists), decentralisation in India (1992).',
                                'subtopics' => [
                                    'Union, State, and Concurrent Lists',
                                    'Special provisions for states (Article 371)',
                                    '73rd and 74th Constitutional Amendments',
                                    'Panchayati Raj structure and women reservation',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Political Parties',
                                'description' => 'Need for political parties, functions, party systems (one-party, two-party, multi-party), national vs regional parties criteria, challenges to political parties, electoral reforms.',
                                'subtopics' => [
                                    'Seven functions of political parties',
                                    'Election Commission recognition criteria for National party',
                                    'Dynastic succession and money power challenges',
                                    'Anti-defection law and affidavit reforms',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Sectors of the Indian Economy',
                                'description' => 'Primary, secondary, and tertiary sectors, historical shifts in sectors, disguised unemployment, NREGA 2005, organized vs unorganized sectors.',
                                'subtopics' => [
                                    'GDP contribution vs Employment percentage',
                                    'Disguised unemployment in agriculture',
                                    'Organised vs Unorganised employment conditions',
                                    'Public sector vs Private sector goals',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Language & Literature',
                        'code' => 'ENG-10',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'A Letter to God & Dust of Snow',
                                'description' => 'G.L. Fuentes story of Lencho unshakeable faith in God, Robert Frost brief lyric on nature therapeutic solace.',
                                'subtopics' => [
                                    'Irony of human character in Lencho',
                                    'Frost Dust of Snow poetic uplift',
                                    'Complex sentences with relative pronouns',
                                    'Formal letter of complaint to postmaster',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Nelson Mandela: Long Walk to Freedom & Fire and Ice',
                                'description' => 'Inaugural address of President Mandela on rainbow nation freedom, Robert Frost apocalyptic dual elemental verse.',
                                'subtopics' => [
                                    'Mandela twin obligations and courage',
                                    'Frost Fire and Ice symbolism of desire & hatred',
                                    'Subject-Verb Concord rules',
                                    'Analytical paragraph on democratic ideals',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Two Stories About Flying & A Tiger in the Zoo',
                                'description' => 'Young seagull first flight courage, mysterious black aeroplane guidance, Leslie Norris captive tiger poem.',
                                'subtopics' => [
                                    'Overcoming psychological fear',
                                    'Leslie Norris imagery of zoo vs wild jungle',
                                    'Direct to Indirect speech questions and imperatives',
                                    'Diary entry of the pilot',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'From the Diary of Anne Frank & Amanda!',
                                'description' => 'Anne Frank voice from the Amsterdam secret annex, Robin Klein verse on adolescent longing for freedom.',
                                'subtopics' => [
                                    'Anne Frank existential adolescent honesty',
                                    'Amanda rebellious daydreaming persona',
                                    'Tense consistency and passive transformations',
                                    'Article on child mental health',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Glimpses of India & The Trees',
                                'description' => 'A Baker from Goa, Coorg coffee plantations, Tea from Assam, Adrienne Rich feminist ecological verse of escaping trees.',
                                'subtopics' => [
                                    'Cultural heritage of Goan paders',
                                    'Kodavu warrior traditions of Coorg',
                                    'Legendary origins of tea discovery',
                                    'Adrienne Rich poetic revolt of nature',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Madam Rides the Bus & Fog',
                                'description' => 'Eight-year-old Valli meticulously planned solo bus journey, Carl Sandburg haiku-like feline fog poem.',
                                'subtopics' => [
                                    'Valli independent planning and observant curiosity',
                                    'Sandburg metaphor of fog on little cat feet',
                                    'Prepositions and phrasal verbs in action',
                                    'Narrative composition on first solo travel',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Information Technology',
                        'code' => 'IT-10',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Digital Documentation (Advanced)',
                                'description' => 'Styles and formatting, inserting and modifying images in documents, creating and using templates, table of contents.',
                                'subtopics' => [
                                    'Paragraph, Character, Page Styles',
                                    'Text wrapping and Image alignment',
                                    'Creating Custom Document Templates',
                                    'Generating Automatic Table of Contents',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Electronic Spreadsheet (Advanced)',
                                'description' => 'Consolidating data, subtotal calculations, What-If Analysis (Scenarios, Goal Seek, Solver), linking spreadsheets, macros.',
                                'subtopics' => [
                                    'Consolidating data across multiple sheets',
                                    'Goal Seek reverse calculation',
                                    'What-If Scenarios modeling',
                                    'Recording and running spreadsheet Macros',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Database Management System (DBMS & SQL)',
                                'description' => 'RDBMS concepts, tables, forms, queries, reports, primary keys, SQL queries (SELECT, INSERT, UPDATE, DELETE).',
                                'subtopics' => [
                                    'Relational data integrity constraints',
                                    'Primary Key vs Foreign Key links',
                                    'Executing SQL queries with WHERE and ORDER BY',
                                    'Designing Input Forms and Output Reports',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Web Applications and Security',
                                'description' => 'Accessibility options, networking fundamentals, instant messaging, online transactions safety, workplace safety and hazards.',
                                'subtopics' => [
                                    'Computer accessibility options for disabilities',
                                    'Client-Server and P2P network models',
                                    'Online banking security protocols (HTTPS, OTP)',
                                    'First aid protocols for electric shock & fire',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Employability Skills & Entrepreneurship',
                                'description' => 'Communication methods, self-management techniques, stress mitigation, green economy entrepreneurship.',
                                'subtopics' => [
                                    'Verbal, Non-verbal, Written communication',
                                    'Time management matrix & Eisenhower quadrants',
                                    'Role of entrepreneurs in Indian economy',
                                    'Sustainable development & Green jobs',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Introduction to Artificial Intelligence & Ethics',
                                'description' => 'AI Project Cycle (Problem Scoping, Data Acquisition, Data Exploration, Modelling, Evaluation), ethical concerns (bias, privacy).',
                                'subtopics' => [
                                    'AI vs Machine Learning vs Deep Learning',
                                    'Five stages of AI Project Cycle',
                                    'Data privacy and algorithmic bias',
                                    'Computer Vision and NLP everyday applications',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            11 => [
                'subjects' => [
                    [
                        'name' => 'Physics',
                        'code' => 'PHY-11',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Units and Measurements',
                                'description' => 'SI standard units, dimensional analysis, principle of homogeneity, error analysis, significant figures.',
                                'subtopics' => [
                                    'Seven base SI units and supplementary units',
                                    'Dimensional formulas for derived quantities',
                                    'Checking equation correctness using dimensions',
                                    'Propagation of relative and percentage errors',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Motion in a Straight Line',
                                'description' => 'Position-time graphs, instantaneous velocity, acceleration, derivation of kinematic equations using calculus.',
                                'subtopics' => [
                                    'Instantaneous velocity v = dx/dt',
                                    'Calculus derivation of kinematic equations',
                                    'Stopping distance and reaction time',
                                    'Relative velocity in one dimension',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Motion in a Plane (Vectors & Projectiles)',
                                'description' => 'Scalars and vectors, vector addition (triangle and parallelogram laws), dot and cross products, projectile motion trajectory.',
                                'subtopics' => [
                                    'Parallelogram Law of Vector Addition',
                                    'Scalar (Dot) and Vector (Cross) products',
                                    'Projectile trajectory y = x·tanθ - (gx²)/(2u²cos²θ)',
                                    'Maximum height, time of flight, horizontal range',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Laws of Motion',
                                'description' => 'Newton laws of motion, impulse, momentum conservation, friction (static, kinetic, rolling), banking of roads.',
                                'subtopics' => [
                                    'Impulse-momentum theorem J = Δp',
                                    'Connected motion of bodies over pulleys',
                                    'Angle of friction and angle of repose',
                                    'Banking of curved roads v = √(rg·tanθ)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Work, Energy and Power',
                                'description' => 'Work-energy theorem, potential energy of a spring, conservative vs non-conservative forces, elastic and inelastic collisions.',
                                'subtopics' => [
                                    'Work-Energy Theorem proof W = ΔKE',
                                    'Potential energy of elastic spring 1/2kx²',
                                    'Conservative forces and curl condition',
                                    'One-dimensional elastic collision velocities',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'System of Particles and Rotational Motion',
                                'description' => 'Center of mass, torque, angular momentum conservation, moment of inertia, parallel and perpendicular axis theorems.',
                                'subtopics' => [
                                    'Center of mass of two-particle and rigid body',
                                    'Torque τ = r × F and Angular momentum L = r × p',
                                    'Conservation of angular momentum applications',
                                    'Moment of inertia of ring, disc, cylinder, sphere',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Gravitation',
                                'description' => 'Kepler laws of planetary motion, universal law of gravitation, variation of g with altitude/depth, escape velocity, orbital velocity.',
                                'subtopics' => [
                                    'Kepler three laws of planetary orbits',
                                    'Acceleration due to gravity at height h and depth d',
                                    'Gravitational potential energy U = -GMm/r',
                                    'Escape velocity ve = √(2gR) and Geostationary satellites',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Mechanical Properties of Solids',
                                'description' => 'Elastic behavior, stress-strain curve, Hooke law, Young modulus, Bulk modulus, Shear modulus, Poisson ratio.',
                                'subtopics' => [
                                    'Hooke law and elastic limit',
                                    'Complete stress-strain curve analysis',
                                    'Young modulus Y = (F·L)/(A·ΔL)',
                                    'Elastic potential energy density 1/2×stress×strain',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Mechanical Properties of Fluids',
                                'description' => 'Pascal law, hydraulic lift, streamline and turbulent flow, equation of continuity, Bernoulli theorem, surface tension, Stokes law.',
                                'subtopics' => [
                                    'Pascal law and hydraulic press multiplication',
                                    'Equation of continuity A1v1 = A2v2',
                                    'Bernoulli theorem proof P + 1/2ρv² + ρgh = constant',
                                    'Terminal velocity derivation and Stokes law',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Thermal Properties of Matter',
                                'description' => 'Thermal expansion (linear, superficial, cubical), heat capacity, specific heat, calorimetry, Newton law of cooling, Wien displacement law.',
                                'subtopics' => [
                                    'Relations between α, β, and γ coefficients',
                                    'Calorimetry principle and phase change latent heats',
                                    'Newton law of cooling derivation',
                                    'Stefan-Boltzmann law and Wien displacement law',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Thermodynamics',
                                'description' => 'Zeroth law of thermodynamics, First law (ΔQ = ΔU + ΔW), isothermal and adiabatic processes, Second law, Carnot engine efficiency.',
                                'subtopics' => [
                                    'First Law of Thermodynamics applications',
                                    'Work done in Isothermal (W = nRT·ln(V2/V1))',
                                    'Work done in Adiabatic process (PV^γ = constant)',
                                    'Carnot cycle efficiency η = 1 - T2/T1',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Kinetic Theory of Gases',
                                'description' => 'Molecular nature of matter, kinetic theory postulates, pressure of an ideal gas, degrees of freedom, law of equipartition of energy.',
                                'subtopics' => [
                                    'Pressure formula P = 1/3·ρ·v_rms²',
                                    'Root mean square speed v_rms = √(3RT/M)',
                                    'Degrees of freedom of monoatomic, diatomic gases',
                                    'Molar specific heat capacities Cp and Cv (Mayer formula)',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Oscillations (Simple Harmonic Motion)',
                                'description' => 'Simple harmonic motion equation d²x/dt² + ω²x = 0, kinetic and potential energy in SHM, simple pendulum time period, loaded spring.',
                                'subtopics' => [
                                    'Displacement, velocity, acceleration in SHM',
                                    'Kinetic energy and potential energy graphs in SHM',
                                    'Simple pendulum time period T = 2π√(L/g)',
                                    'Series and parallel combination of springs',
                                ],
                            ],
                            [
                                'number' => 14,
                                'title' => 'Waves',
                                'description' => 'Transverse and longitudinal waves, speed of sound in gases (Laplace correction), principle of superposition, standing waves, beats.',
                                'subtopics' => [
                                    'Wave equation y = A·sin(kx - ωt)',
                                    'Newton formula and Laplace correction for sound speed',
                                    'Standing waves in strings and organ pipes',
                                    'Beat frequency f_beat = |f1 - f2|',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Chemistry',
                        'code' => 'CHEM-11',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Some Basic Concepts of Chemistry',
                                'description' => 'Mole concept, molar mass, percentage composition, empirical and molecular formulas, stoichiometry, limiting reagent, molarity, molality.',
                                'subtopics' => [
                                    'Avogadro constant and mole calculations',
                                    'Empirical formula determination from % composition',
                                    'Limiting reagent stoichiometric calculations',
                                    'Molarity (M) and Molality (m) conversions',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Structure of Atom',
                                'description' => 'Bohr atomic model, de Broglie dual nature (λ = h/p), Heisenberg uncertainty principle, quantum numbers, Aufbau principle, Hund rule.',
                                'subtopics' => [
                                    'Rydberg formula for hydrogen emission lines',
                                    'de Broglie matter wave equation',
                                    'Heisenberg uncertainty Δx·Δp ≥ h/4π',
                                    'Quantum numbers (n, l, m, s) and orbital shapes',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Classification of Elements and Periodicity',
                                'description' => 'Modern periodic law, s, p, d, f blocks, periodic trends in atomic radii, ionic radii, ionisation enthalpy, electron gain enthalpy, electronegativity.',
                                'subtopics' => [
                                    'Effective nuclear charge Z_eff and shielding',
                                    'First and second ionisation enthalpy anomalies',
                                    'Electron gain enthalpy trends (Cl > F anomaly)',
                                    'Pauling scale of electronegativity',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Chemical Bonding and Molecular Structure',
                                'description' => 'Kossel-Lewis approach, ionic and covalent bonding, VSEPR theory, valence bond theory, hybridization (sp, sp², sp³, sp³d), molecular orbital theory.',
                                'subtopics' => [
                                    'VSEPR molecular geometries (linear to octahedral)',
                                    'Hybridization derivation for CH4, PCl5, SF6',
                                    'Molecular Orbital configurations for O2 and N2',
                                    'Hydrogen bonding (inter vs intramolecular)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Chemical Thermodynamics',
                                'description' => 'First law of thermodynamics, enthalpy changes (ΔH), Hess law of constant heat summation, entropy (S), Gibbs free energy change (ΔG = ΔH - TΔS), spontaneity.',
                                'subtopics' => [
                                    'Relation between ΔH and ΔU (ΔH = ΔU + ΔngRT)',
                                    'Hess Law calculations for reaction enthalpies',
                                    'Second Law and Entropy concept',
                                    'Gibbs free energy and criteria for spontaneity',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Equilibrium',
                                'description' => 'Law of chemical equilibrium, equilibrium constant Kc and Kp, Le Chatelier principle, ionic equilibrium, pH calculation, buffer solutions, solubility product.',
                                'subtopics' => [
                                    'Relation between Kp and Kc (Kp = Kc(RT)^Δn)',
                                    'Le Chatelier principle on temperature/pressure/catalyst',
                                    'Henderson-Hasselbalch equation for buffer pH',
                                    'Common ion effect and Solubility product Ksp',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Redox Reactions',
                                'description' => 'Concept of oxidation and reduction, oxidation number rules, balancing redox equations (ion-electron and oxidation number methods).',
                                'subtopics' => [
                                    'Rules for assigning oxidation numbers',
                                    'Balancing redox in acidic medium',
                                    'Balancing redox in basic medium',
                                    'Electrochemical series and displacement reactions',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Organic Chemistry: Some Basic Principles and Techniques',
                                'description' => 'Classification and IUPAC nomenclature, inductive effect, resonance, electromeric effect, hyperconjugation, reaction intermediates, purification methods.',
                                'subtopics' => [
                                    'IUPAC naming of polyfunctional organic molecules',
                                    'Inductive (+I, -I) and Mesomeric (+M, -M) effects',
                                    'Stability of carbocations, carbanions, free radicals',
                                    'Chromatography and distillation purification',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Hydrocarbons',
                                'description' => 'Alkanes (Wurtz reaction, conformation of ethane), Alkenes (Markovnikov rule, ozonolysis), Alkynes (acidity of ethyne), Aromatic hydrocarbons (benzene resonance, Friedel-Crafts).',
                                'subtopics' => [
                                    'Wurtz reaction and Kolbe electrolytic synthesis',
                                    'Markovnikov vs Anti-Markovnikov addition mechanism',
                                    'Ozonolysis of alkenes for structure elucidation',
                                    'Electrophilic aromatic substitution in Benzene',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-11',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Sets',
                                'description' => 'Roster and set-builder forms, empty set, finite and infinite sets, subsets, power set, universal set, Venn diagrams, union and intersection, De Morgan laws.',
                                'subtopics' => [
                                    'Subsets and Power set 2^n cardinality',
                                    'Venn diagram operations',
                                    'De Morgan Laws algebraic proofs',
                                    'Word problems on cardinality n(A ∪ B)',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Relations and Functions',
                                'description' => 'Cartesian product of sets, definition of relation, domain and range, functions as special relations, polynomial, rational, modulus, signum, greatest integer functions.',
                                'subtopics' => [
                                    'Cartesian product A × B properties',
                                    'Domain, Codomain, and Range of relations',
                                    'Graphs of Modulus |x| and Signum sgn(x)',
                                    'Greatest Integer Function [x] properties',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Trigonometric Functions',
                                'description' => 'Radian measure (π radians = 180°), trigonometric functions in unit circle, compound angle formulas, multiple and submultiple angle identities.',
                                'subtopics' => [
                                    'Arc length formula s = r·θ',
                                    'Compound angles: sin(A ± B), cos(A ± B)',
                                    'Multiple angles: sin2A, cos2A, tan2A identities',
                                    'Product to sum and sum to product transformations',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Complex Numbers and Quadratic Equations',
                                'description' => 'Need for complex numbers, imaginary unit i (i² = -1), algebraic operations, modulus and conjugate, representation on Argand plane, quadratic equations with complex roots.',
                                'subtopics' => [
                                    'Algebraic properties of complex numbers z = a + ib',
                                    'Modulus |z| = √(a² + b²) and Conjugate z*',
                                    'Argand plane representation',
                                    'Solving ax² + bx + c = 0 where D < 0',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Linear Inequalities',
                                'description' => 'Linear inequalities in one variable and two variables, algebraic solutions, graphical solutions of systems of linear inequalities.',
                                'subtopics' => [
                                    'Rules for solving algebraic inequalities',
                                    'Representation of solution on real number line',
                                    'Shading half-planes in Cartesian plane',
                                    'Feasible region for system of linear inequalities',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Permutations and Combinations',
                                'description' => 'Fundamental principle of counting (multiplication and addition rules), factorial notation (n!), permutations formula nPr, combinations formula nCr.',
                                'subtopics' => [
                                    'Fundamental Principle of Counting',
                                    'Permutations with and without repetition nPr',
                                    'Combinations selection formula nCr = n!/[r!(n-r)!]',
                                    'Property: nCr + nCr-1 = n+1Cr (Pascal rule)',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Binomial Theorem',
                                'description' => 'Statement and proof of Binomial Theorem for positive integral indices, general and middle terms in expansion (a + b)^n.',
                                'subtopics' => [
                                    'Binomial Theorem formula expansion',
                                    'General term Tr+1 = nCr · a^(n-r) · b^r',
                                    'Middle term determination for even/odd n',
                                    'Binomial coefficients identities',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Sequences and Series',
                                'description' => 'Arithmetic Progression review, Geometric Progression (GP), general term an = ar^(n-1), sum of first n terms Sn, sum of infinite GP, geometric mean.',
                                'subtopics' => [
                                    'nth term of a Geometric Progression',
                                    'Sum of first n terms of GP Sn = a(r^n - 1)/(r - 1)',
                                    'Sum of infinite GP S_inf = a/(1 - r)',
                                    'Relation between AM and GM (AM ≥ GM)',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Straight Lines',
                                'description' => 'Slope of line m = tanθ, angle between two lines, various forms of line equations (point-slope, slope-intercept, intercept, normal), distance of point from line.',
                                'subtopics' => [
                                    'Slope formula m = (y2-y1)/(x2-x1)',
                                    'Point-slope and Two-point line forms',
                                    'Intercept form (x/a + y/b = 1) and Normal form',
                                    'Perpendicular distance d = |ax1 + by1 + c|/√(a² + b²)',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Conic Sections',
                                'description' => 'Sections of a cone, standard equations and properties of circle, parabola, ellipse, and hyperbola.',
                                'subtopics' => [
                                    'Equation of circle (x-h)² + (y-k)² = r²',
                                    'Parabola standard forms (y² = 4ax, x² = 4ay)',
                                    'Ellipse: Eccentricity, Foci, Major/Minor axes',
                                    'Hyperbola: Asymptotes, Foci, Conjugate axis',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Introduction to Three-Dimensional Geometry',
                                'description' => 'Coordinate axes and coordinate planes in 3D, coordinates of a point in octants, distance formula, section formula.',
                                'subtopics' => [
                                    'Eight octants signs in 3D space',
                                    '3D Distance formula d = √[(x2-x1)² + (y2-y1)² + (z2-z1)²]',
                                    'Internal and external division section formula',
                                    'Centroid of a triangle in 3D',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Limits and Derivatives (Introduction to Calculus)',
                                'description' => 'Intuitive idea of limit, standard limits (lim x→0 sinx/x = 1), derivative at a point, first principle of derivative, product and quotient rules.',
                                'subtopics' => [
                                    'Algebra of limits and sandwich theorem',
                                    'Evaluation of trigonometric limits',
                                    'First principle derivation f′(x) = lim [f(x+h)-f(x)]/h',
                                    'Product rule (uv)′ and Quotient rule (u/v)′',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Statistics',
                                'description' => 'Measures of dispersion: range, mean deviation about mean, mean deviation about median, variance and standard deviation of grouped data.',
                                'subtopics' => [
                                    'Mean deviation about mean and median',
                                    'Variance formula σ² = 1/N · Σfi(xi - x̄)²',
                                    'Standard deviation σ and shortcut method',
                                    'Coefficient of variation comparison',
                                ],
                            ],
                            [
                                'number' => 14,
                                'title' => 'Probability',
                                'description' => 'Random experiments, sample space, event types (mutually exclusive, exhaustive), axiomatic approach to probability, addition theorem P(A ∪ B).',
                                'subtopics' => [
                                    'Sample space generation for complex experiments',
                                    'Mutually exclusive and exhaustive events',
                                    'Axiomatic probability conditions',
                                    'Addition theorem P(A ∪ B) = P(A) + P(B) - P(A ∩ B)',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Biology',
                        'code' => 'BIO-11',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Diversity in the Living World',
                                'description' => 'What is living, biodiversity, taxonomic hierarchy (kingdom, phylum, class, order, family, genus, species), binomial nomenclature.',
                                'subtopics' => [
                                    'Characteristics of life and homeostasis',
                                    'Binomial nomenclature by Carl Linnaeus',
                                    'Taxonomic categories from species to kingdom',
                                    'Botanical gardens, herbaria, museums, keys',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Biological Classification',
                                'description' => 'Five kingdom classification (Whittaker): Monera, Protista, Fungi, Plantae, Animalia; viruses, viroids, prions, lichens.',
                                'subtopics' => [
                                    'Kingdom Monera: Archaebacteria and Eubacteria',
                                    'Kingdom Protista: Chrysophytes, Dinoflagellates, Protozoa',
                                    'Kingdom Fungi: Phycomycetes, Ascomycetes, Basidiomycetes',
                                    'Acellular agents: Viruses, Viroids, Prions, Lichens',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Cell: The Unit of Life',
                                'description' => 'Cell theory, prokaryotic vs eukaryotic cell, fluid mosaic model of plasma membrane, endomembrane system, mitochondria, chloroplasts, nucleus.',
                                'subtopics' => [
                                    'Cell Theory by Schleiden and Schwann',
                                    'Singer-Nicolson Fluid Mosaic membrane model',
                                    'Endomembrane: ER, Golgi, Lysosomes, Vacuoles',
                                    'Semiconservative DNA in Mitochondria & Plastids',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Biomolecules',
                                'description' => 'Chemical analysis of living tissues, carbohydrates, lipids, proteins (primary to quaternary structure), nucleic acids, enzymes and enzyme kinetics.',
                                'subtopics' => [
                                    'Monosaccharides and Glycosidic bonds',
                                    'Phospholipids and Triglyceride structure',
                                    'Peptide bond and Protein structural hierarchy',
                                    'Enzyme active site, Michaelis constant Km, inhibition',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Cell Cycle and Cell Division',
                                'description' => 'Phases of cell cycle (G1, S, G2, M), Mitosis stages (prophase, metaphase, anaphase, telophase), Meiosis I and II, crossing over, significance.',
                                'subtopics' => [
                                    'Interphase and DNA replication in S phase',
                                    'Mitotic spindle assembly and chromosome separation',
                                    'Meiosis I Prophase I stages (Leptotene to Diakinesis)',
                                    'Synaptonemal complex and Crossing over',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Photosynthesis in Higher Plants',
                                'description' => 'Chloroplast structure, pigments, light reactions, Z-scheme of electron transport, photophosphorylation, Calvin cycle (C3), Hatch-Slack pathway (C4).',
                                'subtopics' => [
                                    'Light harvesting complexes (PSI & PSII)',
                                    'Chemiosmotic ATP synthesis hypothesis',
                                    'Calvin Cycle (Carboxylation, Reduction, Regeneration)',
                                    'Kranz anatomy in C4 plants and Photorespiration',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Computer Science',
                        'code' => 'CS-11',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Computer System Overview & Architecture',
                                'description' => 'Von Neumann architecture, memory hierarchy, operating system functions, compilation vs interpretation.',
                                'subtopics' => [
                                    'Von Neumann stored-program concept',
                                    'Cache, Virtual Memory, and Secondary storage',
                                    'Assemblers, Compilers, and Interpreters',
                                    'Open source software and FOSS licensing',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Python Programming Fundamentals',
                                'description' => 'Tokens in Python, data types, mutable vs immutable types, expressions, operator precedence, type casting.',
                                'subtopics' => [
                                    'Keywords, Identifiers, Literals in Python',
                                    'Mutable (lists, dicts) vs Immutable (tuples, strings)',
                                    'Implicit vs Explicit type conversion',
                                    'Interactive script execution and debugging',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Conditional and Looping Constructs',
                                'description' => 'if, if-elif-else, nested conditionals, for loops, while loops, jump statements (break, continue), loop with else.',
                                'subtopics' => [
                                    'Flow of control and indentation blocks',
                                    'Nested for and while loop constructs',
                                    'break and continue in search algorithms',
                                    'Prime number generation and Fibonacci series',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Strings and Text Processing in Python',
                                'description' => 'String traversal, indexing, slicing, string methods (.find(), .replace(), .isdigit(), .join()), formatting.',
                                'subtopics' => [
                                    'Positive and negative string indexing',
                                    'String slicing [start:stop:step]',
                                    'String validation and transformation methods',
                                    'Palindrome verification and anagram detection',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Lists and Tuples in Python',
                                'description' => 'List operations, list slicing, list comprehension, tuple packing and unpacking, immutable tuple safety.',
                                'subtopics' => [
                                    'List manipulation methods (append, extend, pop)',
                                    'List comprehension syntax [x for x in seq if cond]',
                                    'Tuples creation and packing/unpacking',
                                    'Matrix representation using nested lists',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Dictionaries and Hash Maps',
                                'description' => 'Key-value pairs, dictionary methods (.keys(), .values(), .items(), .get()), frequency counting algorithms.',
                                'subtopics' => [
                                    'Keys uniqueness and hashability constraint',
                                    'Dictionary methods: update, pop, clear',
                                    'Word frequency counter implementation',
                                    'Nested dictionary schemas for records',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Core',
                        'code' => 'ENG-11',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The Portrait of a Lady (Khushwant Singh) & A Photograph',
                                'description' => 'Khushwant Singh tender memoir of his grandmother, Shirley Toulson poignant verse on mother youth photograph.',
                                'subtopics' => [
                                    'Khushwant Singh grandmother character study',
                                    'Toulson reflection on mortality and loss',
                                    'Prepositional idioms in prose',
                                    'Notice and advertisement drafting',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'We’re Not Afraid to Die... if We Can All Be Together',
                                'description' => 'Gordon Cook and Alan East epic oceanic survival narrative aboard Wavewalker in Indian Ocean storms.',
                                'subtopics' => [
                                    'Maritime navigation and survival fortitude',
                                    'Childhood heroism and courage in crisis',
                                    'Passive voice transformations',
                                    'Poster design on disaster preparedness',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Discovering Tut: The Saga Continues & The Laburnum Top',
                                'description' => 'A.R. Williams archaeological forensic CT scan of Pharaoh Tutankhamun, Ted Hughes dynamic yellow laburnum poem.',
                                'subtopics' => [
                                    'Forensic archaeology and Egyptian history',
                                    'Hughes sensory avian imagery of goldfinch',
                                    'Reported speech with complex sentences',
                                    'Speech writing on cultural preservation',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'The Voice of the Rain & Childhood',
                                'description' => 'Walt Whitman poem on rain as eternal poem of earth, Markus Natten contemplation on the disappearance of innocence.',
                                'subtopics' => [
                                    'Whitman personification of water cycle',
                                    'Natten philosophical critique of adult hypocrisy',
                                    'Clause analysis (noun, adjective, adverb clauses)',
                                    'Debate writing on traditional schooling',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            12 => [
                'subjects' => [
                    [
                        'name' => 'Physics',
                        'code' => 'PHY-12',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Electric Charges and Fields',
                                'description' => 'Coulombs law in vector form, superposition principle, electric field lines, electric dipole in uniform field, Gauss law and applications.',
                                'subtopics' => [
                                    'Coulomb law F = 1/(4πε0) · (q1q2/r²)',
                                    'Electric flux Φ = ∮ E · dA',
                                    'Gauss law proof and application to infinite straight wire',
                                    'Field of uniformly charged infinite plane sheet and hollow sphere',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Electrostatic Potential and Capacitance',
                                'description' => 'Electric potential due to point charge, equipotential surfaces, potential energy of dipole, parallel plate capacitor, dielectrics.',
                                'subtopics' => [
                                    'Potential V = q/(4πε0·r)',
                                    'Equipotential surfaces properties',
                                    'Capacitance C = ε0·A/d with dielectric slab',
                                    'Energy stored in capacitor U = 1/2·CV² and loss in sharing',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Current Electricity',
                                'description' => 'Drift velocity vd = -eEτ/m, Ohms law microscopic form j = σE, temperature dependence of resistivity, Kirchhoffs rules, Wheatstone bridge.',
                                'subtopics' => [
                                    'Derivation of Ohm law from drift velocity',
                                    'Internal resistance of cell and terminal voltage',
                                    'Kirchhoff Current Law (KCL) & Voltage Law (KVL)',
                                    'Wheatstone Bridge balance condition P/Q = R/S',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Moving Charges and Magnetism',
                                'description' => 'Biot-Savart law, magnetic field on axis of circular loop, Ampere circuital law, solenoid, Lorentz force, force between parallel currents.',
                                'subtopics' => [
                                    'Biot-Savart law dB = (μ0/4π) · (I dl × r̂)/r²',
                                    'Field of long straight wire and circular coil',
                                    'Ampere Circuital Law ∮ B · dl = μ0·I_enclosed',
                                    'Definition of 1 Ampere from parallel current force',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Magnetism and Matter',
                                'description' => 'Bar magnet as equivalent solenoid, magnetic dipole moment, Earths magnetic field elements, diamagnetic, paramagnetic, and ferromagnetic materials.',
                                'subtopics' => [
                                    'Magnetic field lines vs electric field lines',
                                    'Earth magnetic field: Declination, Dip, Horizontal component',
                                    'Diamagnetism, Paramagnetism, Ferromagnetism domains',
                                    'Curie temperature and Hysteresis loop',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Electromagnetic Induction',
                                'description' => 'Faradays experiments, Faradays law of induction, Lenzs law and conservation of energy, motional EMF, eddy currents, self and mutual inductance.',
                                'subtopics' => [
                                    'Faraday law ε = -dΦB/dt',
                                    'Lenz law proof of energy conservation',
                                    'Motional EMF ε = B·l·v in magnetic field',
                                    'Self-inductance L and Mutual inductance M formulas',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Alternating Current',
                                'description' => 'Peak and RMS value of AC (I_rms = I0/√2), AC circuit with L, C, R in series, phasor diagrams, resonance, Q-factor, AC generator, transformer.',
                                'subtopics' => [
                                    'Derivation of RMS current and voltage',
                                    'Series LCR circuit impedance Z = √[R² + (XL-XC)²]',
                                    'Resonance frequency ω0 = 1/√(LC) and Quality factor',
                                    'Transformer working and power loss mechanisms',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Electromagnetic Waves',
                                'description' => 'Displacement current (Maxwell correction to Ampere law), characteristics of EM waves, electromagnetic spectrum from radio to gamma rays.',
                                'subtopics' => [
                                    'Maxwell displacement current I_d = ε0·(dΦE/dt)',
                                    'Transverse nature of EM waves (E ⊥ B ⊥ c)',
                                    'Speed of light in vacuum c = 1/√(μ0ε0)',
                                    'Electromagnetic spectrum wavelength and frequency table',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Ray Optics and Optical Instruments',
                                'description' => 'Total internal reflection, optical fibers, refraction at spherical surfaces, lens maker formula, compound microscope, astronomical telescope.',
                                'subtopics' => [
                                    'Total internal reflection and critical angle',
                                    'Lens Maker Formula 1/f = (μ-1)(1/R1 - 1/R2)',
                                    'Compound Microscope magnifying power',
                                    'Astronomical Telescope in normal and near point adjustment',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Wave Optics',
                                'description' => 'Huygens wave theory of light, laws of reflection and refraction using wavelets, Youngs double slit experiment, fringe width derivation, diffraction.',
                                'subtopics' => [
                                    'Huygens principle and wavefronts',
                                    'Proof of Snell Law using wave theory',
                                    'Young Double Slit fringe width β = λD/d derivation',
                                    'Single slit diffraction central maximum width',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Dual Nature of Radiation and Matter',
                                'description' => 'Photoelectric effect, Hertz and Lenard observations, Einsteins photoelectric equation (K_max = hν - Φ0), de Broglie wavelength of electron.',
                                'subtopics' => [
                                    'Photoelectric effect key experimental graphs',
                                    'Einstein photoelectric equation derivation',
                                    'Threshold frequency and work function Φ0',
                                    'de Broglie wavelength of accelerated electron λ = 12.27/√V Å',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Atoms',
                                'description' => 'Alpha particle scattering, Rutherford nuclear model, Bohr model of hydrogen atom, energy levels En = -13.6/n² eV, hydrogen spectral series.',
                                'subtopics' => [
                                    'Rutherford scattering impact parameter and distance of closest approach',
                                    'Bohr quantization condition L = nh/2π',
                                    'Radius of Bohr orbit rn = 0.529·n² Å',
                                    'Hydrogen emission series (Lyman, Balmer, Paschen, Brackett, Pfund)',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Nuclei',
                                'description' => 'Composition and size of nucleus (R = R0·A^(1/3)), mass-energy relation E = mc², mass defect, binding energy per nucleon curve, nuclear fission, fusion.',
                                'subtopics' => [
                                    'Nuclear density constancy independent of mass number',
                                    'Mass defect Δm and Binding energy BE = Δm·c²',
                                    'Binding energy per nucleon curve stability analysis',
                                    'Nuclear fission (U-235) and stellar nuclear fusion',
                                ],
                            ],
                            [
                                'number' => 14,
                                'title' => 'Semiconductor Electronics',
                                'description' => 'Intrinsic and extrinsic semiconductors (p-type and n-type), p-n junction diode forward and reverse characteristics, half-wave and full-wave rectifiers.',
                                'subtopics' => [
                                    'Energy band diagram (valence, conduction, forbidden band)',
                                    'Doping with trivalent and pentavalent atoms',
                                    'p-n junction depletion layer and barrier potential',
                                    'Full-wave bridge rectifier circuit and efficiency',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Chemistry',
                        'code' => 'CHEM-12',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Solutions and Colligative Properties',
                                'description' => 'Types of solutions, Raoults law for volatile and non-volatile solutes, colligative properties, Van t Hoff factor.',
                                'subtopics' => [
                                    'Ideal vs non-ideal solutions (azeotropes)',
                                    'Relative lowering of vapour pressure',
                                    'Freezing point depression ΔTf = Kf·m',
                                    'Van t Hoff factor i and dissociation %',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Electrochemistry',
                                'description' => 'Electrochemical cells, Nernst equation for cell EMF, Kohlrauschs law of independent migration of ions, Faradays laws of electrolysis, fuel cells.',
                                'subtopics' => [
                                    'Nernst equation E_cell = E° - (0.0591/n)logQ',
                                    'Molar conductivity Λm vs √C graphs',
                                    'Kohlrausch law calculations',
                                    'Lead-acid accumulator and H2-O2 fuel cell',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Chemical Kinetics',
                                'description' => 'Rate of reaction, rate law and specific rate constant, order and molecularity, integrated rate equations for zero and first order, Arrhenius equation.',
                                'subtopics' => [
                                    'Differential and integrated rate laws',
                                    'First order half-life t_1/2 = 0.693/k',
                                    'Pseudo first order reactions',
                                    'Arrhenius equation log(k2/k1) = Ea/2.303R(1/T1 - 1/T2)',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'The d- and f-Block Elements',
                                'description' => 'Transition elements electronic configuration, oxidation states, catalytic properties, interstitial compounds, potassium dichromate and permanganate.',
                                'subtopics' => [
                                    'Variable oxidation states of 3d elements',
                                    'Catalytic and magnetic properties (spin-only formula)',
                                    'Lanthanoid contraction causes and consequences',
                                    'Preparation and oxidising properties of KMnO4 and K2Cr2O7',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Coordination Compounds',
                                'description' => 'Werners theory, ligands, IUPAC nomenclature, isomerism (structural and stereoisomerism), Valence Bond Theory, Crystal Field Theory.',
                                'subtopics' => [
                                    'Chelating and ambidentate ligands',
                                    'IUPAC naming of coordination complexes',
                                    'Crystal Field Splitting Δo and Δt',
                                    'Magnetic moment μ = √(n(n+2)) BM',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Haloalkanes and Haloarenes',
                                'description' => 'Nomenclature, methods of preparation, physical and chemical properties, SN1 and SN2 reaction mechanisms, stereochemistry, optical inversion.',
                                'subtopics' => [
                                    'SN1 vs SN2 kinetic & stereochemical pathways',
                                    'Walden inversion and carbocation stability',
                                    'Electrophilic substitution in chlorobenzene',
                                    'Grignard reagent preparation and reactions',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Alcohols, Phenols and Ethers',
                                'description' => 'Hydroboration-oxidation, acidity of phenols compared to alcohols, Kolbes reaction, Reimer-Tiemann reaction, Williamsons ether synthesis.',
                                'subtopics' => [
                                    'Preparation of alcohols from alkenes',
                                    'Acidity order with resonance structures',
                                    'Reimer-Tiemann and Kolbe synthesis',
                                    'Williamsons synthesis mechanism (SN2)',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Aldehydes, Ketones & Carboxylic Acids',
                                'description' => 'Nucleophilic addition reactions, Tollens and Fehlings tests, Aldol condensation, Cannizzaro reaction, acidity of carboxylic acids.',
                                'subtopics' => [
                                    'Nucleophilic addition mechanism with HCN',
                                    'Aldol and Cross-Aldol condensation',
                                    'Cannizzaro disproportionation reaction',
                                    'HVZ reaction of carboxylic acids',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Amines and Diazonium Salts',
                                'description' => 'Classification, preparation (Hoffmann bromamide), basic character of amines, Hinsberg test, synthetic applications of diazonium salts.',
                                'subtopics' => [
                                    'Hoffmann Bromamide degradation of amides',
                                    'Gas phase vs aqueous basicity order of amines',
                                    'Hinsberg test distinguishing 1°, 2°, 3° amines',
                                    'Sandmeyer and Gattermann reactions',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Biomolecules',
                                'description' => 'Carbohydrates (monosaccharides, Haworth structures of glucose/fructose), amino acids, peptide bond, primary to quaternary protein structure, DNA/RNA double helix.',
                                'subtopics' => [
                                    'D- and L- configuration of glucose',
                                    'Zwitterion structure of amino acids',
                                    'Denaturation of proteins',
                                    'Nucleotides and phosphodiester linkages in DNA',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Mathematics',
                        'code' => 'MATH-12',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Relations and Functions',
                                'description' => 'Reflexive, symmetric, transitive, and equivalence relations, one-one and onto (bijective) functions, composite functions, invertible functions.',
                                'subtopics' => [
                                    'Equivalence relation proofs and equivalence classes',
                                    'Bijective functions f: A → B conditions',
                                    'Composition of functions (g ∘ f)',
                                    'Inverse function f⁻¹ derivation',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Inverse Trigonometric Functions',
                                'description' => 'Definition, range, domain, principal value branches, graphs of inverse trig functions, elementary properties.',
                                'subtopics' => [
                                    'Principal value branch of arcsin, arccos, arctan',
                                    'Domain and range restrictions',
                                    'Properties: sin⁻¹(x) + cos⁻¹(x) = π/2',
                                    'tan⁻¹x + tan⁻¹y identities',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Matrices',
                                'description' => 'Matrix operations, transpose, symmetric and skew-symmetric matrices, elementary row operations, invertible matrices.',
                                'subtopics' => [
                                    'Matrix multiplication associativity and non-commutativity',
                                    'Symmetric and Skew-symmetric decomposition',
                                    'Elementary row transformations for inverse',
                                    'Symmetric matrix A = 1/2(A + A^T) + 1/2(A - A^T)',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Determinants',
                                'description' => 'Properties of determinants, minors, cofactors, adjoint of matrix, matrix inverse, solving systems of linear equations using matrix method.',
                                'subtopics' => [
                                    'Properties of determinants expansions',
                                    'Adjoint formula adj(A) and A·adj(A) = |A|·I',
                                    'Matrix inverse formula A⁻¹ = (1/|A|)·adj(A)',
                                    'Solving AX = B system of linear equations',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Continuity and Differentiability',
                                'description' => 'Continuity of functions at a point and in an interval, chain rule, implicit differentiation, logarithmic differentiation, second order derivatives.',
                                'subtopics' => [
                                    'Continuity definition via left/right limits',
                                    'Chain rule for composite functions',
                                    'Logarithmic differentiation for y = f(x)^g(x)',
                                    'Second order derivatives d²y/dx²',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Applications of Derivatives',
                                'description' => 'Rate of change of quantities, strictly increasing and decreasing functions, tangents and normals, local and absolute maxima and minima.',
                                'subtopics' => [
                                    'Rate of change modeling (dV/dt)',
                                    'Strictly increasing/decreasing criteria (f′(x) > 0)',
                                    'First Derivative Test for Maxima/Minima',
                                    'Second Derivative Test optimization problems',
                                ],
                            ],
                            [
                                'number' => 7,
                                'title' => 'Integrals (Calculus)',
                                'description' => 'Integration as inverse of differentiation, substitution method, partial fractions, integration by parts, fundamental theorem of calculus, definite integral properties.',
                                'subtopics' => [
                                    'Standard integral formulas',
                                    'Integration by parts (ILATE rule)',
                                    'Integration using partial fractions',
                                    'Definite integral symmetry properties ∫_0^a f(x)dx',
                                ],
                            ],
                            [
                                'number' => 8,
                                'title' => 'Applications of the Integrals',
                                'description' => 'Area under simple curves, area bounded between line and parabola, ellipse, and circle using integration.',
                                'subtopics' => [
                                    'Area under curve ∫ y dx',
                                    'Area bounded between line and parabola',
                                    'Area of ellipse using definite integration',
                                    'Area between intersecting curves',
                                ],
                            ],
                            [
                                'number' => 9,
                                'title' => 'Differential Equations',
                                'description' => 'Order and degree of differential equations, general and particular solutions, variable separable method, homogeneous equations, first order linear differential equations.',
                                'subtopics' => [
                                    'Order vs degree definitions',
                                    'Variable separable method',
                                    'Homogeneous differential equations (y = vx)',
                                    'Integrating factor method dy/dx + Py = Q',
                                ],
                            ],
                            [
                                'number' => 10,
                                'title' => 'Vector Algebra',
                                'description' => 'Vectors and scalars, magnitude and direction cosines, dot and cross products of vectors, projection of vector on a line, scalar triple product.',
                                'subtopics' => [
                                    'Direction cosines and direction ratios',
                                    'Dot product a · b = |a||b|cosθ',
                                    'Cross product a × b = |a||b|sinθ n̂',
                                    'Area of parallelogram and triangle using vectors',
                                ],
                            ],
                            [
                                'number' => 11,
                                'title' => 'Three-Dimensional Geometry',
                                'description' => 'Direction cosines and ratios of a line, vector and Cartesian equations of line, angle between lines, shortest distance between skew lines, equation of plane.',
                                'subtopics' => [
                                    'Vector and Cartesian line equations',
                                    'Shortest distance between two skew lines',
                                    'Vector equation of plane in normal form',
                                    'Angle between line and plane',
                                ],
                            ],
                            [
                                'number' => 12,
                                'title' => 'Linear Programming',
                                'description' => 'Mathematical formulation of LPP, graphical method of solution, bounded and unbounded feasible regions, corner point theorem.',
                                'subtopics' => [
                                    'Formulating objective function Z = ax + by',
                                    'Plotting constraint inequalities on grid',
                                    'Corner Point Method for optimization',
                                    'Unbounded region optimality test',
                                ],
                            ],
                            [
                                'number' => 13,
                                'title' => 'Probability',
                                'description' => 'Conditional probability P(A|B), multiplication theorem of probability, independent events, Bayes theorem, random variable and probability distribution.',
                                'subtopics' => [
                                    'Conditional probability formula P(A|B) = P(A ∩ B)/P(B)',
                                    'Independent events condition P(A ∩ B) = P(A)·P(B)',
                                    'Bayes Theorem for partition of sample space',
                                    'Probability distribution and expected value',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Biology',
                        'code' => 'BIO-12',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Sexual Reproduction in Flowering Plants',
                                'description' => 'Flower structure, microsporogenesis, megasporogenesis, pollination mechanisms, double fertilization, endosperm and embryo development, apomixis.',
                                'subtopics' => [
                                    'Anther anatomy and pollen grain structure',
                                    'Embryo sac (7-celled, 8-nucleate) development',
                                    'Double fertilization (Syngamy + Triple fusion)',
                                    'Apomixis and Polyembryony',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Human Reproduction',
                                'description' => 'Male and female reproductive systems, microscopic anatomy of testis and ovary, spermatogenesis and oogenesis, menstrual cycle, fertilization, implantation, pregnancy, parturition.',
                                'subtopics' => [
                                    'Spermatogenesis vs Oogenesis comparisons',
                                    'Hormonal regulation of Menstrual cycle',
                                    'Capacitation and acrosome reaction in fertilization',
                                    'Placenta endocrine hormones (hCG, hPL, relaxin)',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Reproductive Health',
                                'description' => 'Population explosion, birth control methods, medical termination of pregnancy (MTP), sexually transmitted infections (STIs), assisted reproductive technologies (ART: IVF, ZIFT, GIFT).',
                                'subtopics' => [
                                    'Barrier, hormonal, and surgical contraception',
                                    'MTP Act legal guidelines in India',
                                    'Sexually Transmitted Infections prevention',
                                    'In-vitro fertilization (IVF) and test-tube baby',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Principles of Inheritance and Variation',
                                'description' => 'Mendelian inheritance, chromosomal theory of inheritance, linkage and recombination (Morgan), sex-linked inheritance (color blindness, hemophilia), chromosomal disorders.',
                                'subtopics' => [
                                    'Thomas Hunt Morgan Drosophila linkage experiments',
                                    'Incomplete dominance and Codominance (ABO blood groups)',
                                    'Pedigree analysis charts',
                                    'Mendelian vs Chromosomal disorders (Down, Turner, Klinefelter)',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Molecular Basis of Inheritance',
                                'description' => 'DNA as genetic material (Hershey-Chase), DNA replication (Meselson-Stahl), transcription, genetic code, translation, lac operon, Human Genome Project (HGP), DNA fingerprinting.',
                                'subtopics' => [
                                    'Hershey-Chase bacteriophage experiment',
                                    'Meselson-Stahl semiconservative replication',
                                    'Transcription in eukaryotes (capping, tailing, splicing)',
                                    'Jacob-Monod Lac Operon regulation model',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Biotechnology: Principles and Processes',
                                'description' => 'Recombinant DNA technology tools (restriction endonucleases, DNA ligase, cloning vectors pBR322), PCR amplification, bioreactors, downstream processing.',
                                'subtopics' => [
                                    'Restriction enzymes and sticky ends',
                                    'Plasmids and features of pBR322 cloning vector',
                                    'Polymerase Chain Reaction (PCR: Denaturation, Annealing, Extension)',
                                    'Stirred-tank bioreactors and Downstream processing',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Computer Science',
                        'code' => 'CS-12',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'Python Revision Tour & Advanced Functions',
                                'description' => 'Scope of variables (LEGB rule), default arguments, keyword arguments, returning multiple values, recursion.',
                                'subtopics' => [
                                    'Local, Enclosing, Global, Built-in (LEGB) scope',
                                    'Positional vs Keyword arguments in Python',
                                    'Returning tuples and multiple variables',
                                    'Recursive binary search and factorial',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'File Handling (Text, Binary, CSV)',
                                'description' => 'Opening, reading, writing, and appending files; pickle module for binary serialization; csv module for CSV manipulation.',
                                'subtopics' => [
                                    'Text file modes (r, w, a, r+)',
                                    'seek() and tell() cursor positioning',
                                    'pickle.dump() and pickle.load() for objects',
                                    'csv.reader() and csv.writer() delimiter parsing',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Data Structures: Stacks',
                                'description' => 'Stack linear data structure, LIFO principle, Push and Pop operations using Python lists, stack overflow/underflow, expression evaluation.',
                                'subtopics' => [
                                    'LIFO principle in computing',
                                    'Implementing Push and Pop functions',
                                    'Peak and Display stack operations',
                                    'Infix to Postfix conversion concept',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'Computer Networks & Internet Protocols',
                                'description' => 'OSI and TCP/IP models, IP addressing (IPv4 vs IPv6), MAC address, DNS, HTTP/HTTPS, FTP, SMTP, POP3, VoIP, network security.',
                                'subtopics' => [
                                    'Seven layers of OSI model',
                                    'IPv4 (32-bit) vs IPv6 (128-bit) addressing',
                                    'Domain Name System (DNS) resolution',
                                    'Network protocols: HTTP, HTTPS, FTP, SSH',
                                ],
                            ],
                            [
                                'number' => 5,
                                'title' => 'Database Management using SQL',
                                'description' => 'SQL aggregate functions (COUNT, SUM, AVG, MIN, MAX), GROUP BY and HAVING clauses, JOINS (Equi-join, Natural join), nested subqueries.',
                                'subtopics' => [
                                    'Aggregate functions with NULL handling',
                                    'GROUP BY with HAVING filtering clause',
                                    'Cartesian Product vs Equi-join on Foreign Key',
                                    'Subqueries in WHERE and FROM clauses',
                                ],
                            ],
                            [
                                'number' => 6,
                                'title' => 'Interface Python with SQL Database',
                                'description' => 'Connecting Python to MySQL/PostgreSQL using connector drivers, cursor object, executing queries, fetching rows (fetchone, fetchall), committing transactions.',
                                'subtopics' => [
                                    'Database connection establishment and credentials',
                                    'Cursor object execution of SELECT and INSERT',
                                    'fetchall() vs fetchone() result sets',
                                    'Transaction commit() and rollback() safety',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'English Core',
                        'code' => 'ENG-12',
                        'chapters' => [
                            [
                                'number' => 1,
                                'title' => 'The Last Lesson (Alphonse Daudet) & My Mother at Sixty-Six',
                                'description' => 'Linguistic chauvinism in Franco-Prussian war, M. Hamel farewell French lesson, Kamala Das intimate poem on aging mother.',
                                'subtopics' => [
                                    'Linguistic identity and patriotic loss',
                                    'Kamala Das imagery of corpse-like aging mother',
                                    'Formal letter to Editor on language preservation',
                                    'Article writing on national integration',
                                ],
                            ],
                            [
                                'number' => 2,
                                'title' => 'Lost Spring (Anees Jung) & Keeping Quiet',
                                'description' => 'Stolen childhood of ragpickers in Seemapuri and bangle makers in Firozabad, Pablo Neruda meditative plea for stillness.',
                                'subtopics' => [
                                    'Vicious cycle of poverty and middlemen',
                                    'Child labor abolition legal ethics',
                                    'Neruda call for universal introspection',
                                    'Notice and circular drafting',
                                ],
                            ],
                            [
                                'number' => 3,
                                'title' => 'Deep Water (William Douglas) & A Thing of Beauty',
                                'description' => 'Conquest of childhood water phobia through discipline, John Keats immortal romantic ode to enduring beauty.',
                                'subtopics' => [
                                    'Psychological overcoming of trauma and phobia',
                                    'Keats aesthetic philosophy of joy forever',
                                    'Active and passive transformations in literature',
                                    'Speech on mental wellness',
                                ],
                            ],
                            [
                                'number' => 4,
                                'title' => 'The Rattrap (Selma Lagerlöf) & A Roadside Stand',
                                'description' => 'Human kindness redeeming cynical peddler, Robert Frost poignant social plea for rural roadside vendors.',
                                'subtopics' => [
                                    'Metaphor of the world as a rattrap',
                                    'Edla Willmansson unconditional compassionate grace',
                                    'Frost indictment of urban apathy to rural poor',
                                    'Formal invitation and reply writing',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
