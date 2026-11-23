<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Batch;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\TopicBloomActivity;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollegeClassAndCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or Find College Academic Class (Grade Level 13)
        $collegeClass = AcademicClass::firstOrCreate(
            ['grade_level' => 13],
            [
                'name' => 'College',
                'section' => 'A',
                'academic_year' => '2026-2027',
            ]
        );

        $teacher = User::where('role', 'teacher')->first() ?? User::where('role', 'admin')->first();

        // 2. Create College Batches
        $morningBatch = Batch::firstOrCreate(
            [
                'class_id' => $collegeClass->id,
                'shift' => 'morning',
            ],
            [
                'name' => 'College - Undergrad Morning Scholars',
                'code' => 'COL-UG-AM',
                'description' => 'Morning laboratory & theoretical lecture shift for College undergraduates.',
                'academic_year' => '2026-2027',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'timing_slot' => '08:00 AM - 12:00 PM',
                'is_active' => true,
            ]
        );

        $eveningBatch = Batch::firstOrCreate(
            [
                'class_id' => $collegeClass->id,
                'shift' => 'evening',
            ],
            [
                'name' => 'College - Research & Evening Scholars',
                'code' => 'COL-UG-PM',
                'description' => 'Evening seminar & research practical shift for College undergraduates.',
                'academic_year' => '2026-2027',
                'start_time' => '15:30',
                'end_time' => '19:30',
                'timing_slot' => '03:30 PM - 07:30 PM',
                'is_active' => true,
            ]
        );

        // 3. College Subjects & Topics (Biotechnology & Pure Sciences Focus)
        $collegeCurriculum = [
            [
                'name' => 'Biotechnology & Genetic Engineering',
                'code' => 'BIO-UG101',
                'topics' => [
                    [
                        'chapter_number' => 1,
                        'title' => 'Recombinant DNA Technology & Gene Cloning Vectors',
                        'description' => 'Restriction endonucleases, plasmid vectors (pBR322, pUC19), ligation kinetics, and host transformation methods.',
                        'subtopics' => ['Restriction Enzymes', 'Cloning Vectors', 'Host Competence & Transformation', 'Blue-White Colony Screening'],
                    ],
                    [
                        'chapter_number' => 2,
                        'title' => 'CRISPR-Cas9 & Genome Editing Systems',
                        'description' => 'Molecular architecture of bacterial adaptive immunity, guide RNA design, Cas9 nuclease domains, and off-target mitigation strategies.',
                        'subtopics' => ['Bacterial CRISPR Arrays', 'sgRNA Synthesis', 'Non-Homologous End Joining', 'Homology Directed Repair'],
                    ],
                ]
            ],
            [
                'name' => 'Microbiology & Immunology',
                'code' => 'MIC-UG102',
                'topics' => [
                    [
                        'chapter_number' => 1,
                        'title' => 'Bacterial Growth Kinetics & Continuous Fermentation',
                        'description' => 'Monod growth models, chemostat dynamics, industrial bioreactor design, and downstream product recovery.',
                        'subtopics' => ['Monod Kinetics', 'Chemostats & Turbidostats', 'Bioreactor Aeration', 'Downstream Bioprocessing'],
                    ],
                    [
                        'chapter_number' => 2,
                        'title' => 'Humoral & Cell-Mediated Immune Mechanisms',
                        'description' => 'Major Histocompatibility Complex (MHC I & II), T-cell receptor signaling, monoclonal antibody hybridomas, and vaccine platforms.',
                        'subtopics' => ['MHC Antigen Presentation', 'Hybridoma Technology', 'Cytokines & Signal Transduction', 'mRNA Vaccines'],
                    ],
                ]
            ],
            [
                'name' => 'Biochemistry & Molecular Biology',
                'code' => 'BCH-UG103',
                'topics' => [
                    [
                        'chapter_number' => 1,
                        'title' => 'Enzyme Kinetics & Allosteric Regulation',
                        'description' => 'Michaelis-Menten derivations, Lineweaver-Burk plots, reversible inhibition, and catalytic catalytic triads in serine proteases.',
                        'subtopics' => ['Michaelis-Menten Equation', 'Lineweaver-Burk Analysis', 'Competitive vs Non-competitive Inhibition', 'Allosteric Cooperativity'],
                    ],
                ]
            ],
            [
                'name' => 'Bioinformatics & Computational Genomics',
                'code' => 'BIN-UG104',
                'topics' => [
                    [
                        'chapter_number' => 1,
                        'title' => 'Sequence Alignment Algorithms & Next-Gen Sequencing',
                        'description' => 'Needleman-Wunsch global alignment, Smith-Waterman local alignment, BLAST heuristics, and Illumina sequencing by synthesis.',
                        'subtopics' => ['Dynamic Programming', 'BLAST Algorithm', 'Phylogenetic Trees', 'Variant Calling Pipelines'],
                    ],
                ]
            ],
            [
                'name' => 'Technical English & Research Communication',
                'code' => 'ENG-UG105',
                'topics' => [
                    [
                        'chapter_number' => 1,
                        'title' => 'Scientific Literature Review & Manuscript Drafting',
                        'description' => 'Structure of empirical research papers (IMRaD), literature synthesis, citation integrity, and academic lexicon.',
                        'subtopics' => ['Literature Synthesis Matrix', 'IMRaD Manuscript Structure', 'Reference Management & DOI', 'Academic Tone & Clarity'],
                    ],
                    [
                        'chapter_number' => 2,
                        'title' => 'Technical Seminars, Visual Rhetoric & Poster Defense',
                        'description' => 'Design of scientific conference posters, slide decks, verbal articulation of data, and handling peer questions.',
                        'subtopics' => ['Scientific Poster Layout', 'Slide Deck Cognitive Load', 'Oral Research Articulation', 'Peer Q&A Strategy'],
                    ],
                    [
                        'chapter_number' => 3,
                        'title' => 'Research Proposals, Grant Writing & Academic Ethics',
                        'description' => 'Drafting grant applications, project problem statements, budget defense, and research integrity.',
                        'subtopics' => ['Specific Aims Formulation', 'Methodology Defense', 'Plagiarism & Research Ethics', 'Grant Review Rubrics'],
                    ],
                    [
                        'chapter_number' => 4,
                        'title' => 'Peer Review Protocols, Data Reporting & Viva Voce',
                        'description' => 'Simulated blind peer reviews, replying to reviewers comments, dissertation defense, and thesis viva.',
                        'subtopics' => ['Reviewer Rebuttal Letters', 'Data Visualization Ethics', 'Thesis Viva Voce Preparation', 'Academic Discourse'],
                    ],
                ]
            ],
        ];

        foreach ($collegeCurriculum as $subjData) {
            $subject = AcademicSubject::firstOrCreate(
                [
                    'class_id' => $collegeClass->id,
                    'name' => $subjData['name'],
                ],
                [
                    'code' => $subjData['code'],
                ]
            );

            foreach ($subjData['topics'] as $topData) {
                $topic = CurriculumTopic::firstOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'chapter_number' => $topData['chapter_number'],
                    ],
                    [
                        'title' => $topData['title'],
                        'description' => $topData['description'],
                        'subtopics' => $topData['subtopics'],
                    ]
                );

                // Add 6 Bloom's activities for this topic
                for ($lvl = 1; $lvl <= 6; $lvl++) {
                    $meta = TopicBloomActivity::getLevelMetadata($lvl);
                    TopicBloomActivity::firstOrCreate(
                        [
                            'topic_id' => $topic->id,
                            'bloom_level' => $lvl,
                        ],
                        [
                            'level_name' => $meta['name'],
                            'verb_keywords' => $meta['verbs'],
                            'activity_prompt' => "Level {$lvl} ({$meta['name']}): Investigate {$topic->title} by focusing on {$topData['subtopics'][0]} and analyzing underlying biochemical kinetics.",
                            'rubric_guide' => "Demonstrate collegiate understanding conforming to Level {$lvl} cognitive standards.",
                        ]
                    );
                }

                // Create a sample College DPP for the first topic
                if ($subjData['code'] === 'BIO-UG101' && $topData['chapter_number'] === 1 && $teacher) {
                    $dpp = DailyDpp::firstOrCreate(
                        [
                            'class_id' => $collegeClass->id,
                            'subject_id' => $subject->id,
                            'title' => 'College DPP: Recombinant DNA & Cloning Vectors',
                        ],
                        [
                            'topic_id' => $topic->id,
                            'teacher_id' => $teacher->id,
                            'dpp_number' => 'DPP-UG-01',
                            'type' => 'dpp',
                            'difficulty' => 'advanced',
                            'duration_minutes' => 20,
                            'total_marks' => 8,
                            'assigned_date' => now()->toDateString(),
                        ]
                    );

                    DppQuestion::firstOrCreate(
                        [
                            'dpp_id' => $dpp->id,
                            'question_number' => 1,
                        ],
                        [
                            'question_text' => 'Which characteristic distinguishes Type II restriction endonucleases from Type I and Type III enzymes in genetic engineering?',
                            'option_a' => 'They require ATP hydrolysis to cleave DNA',
                            'option_b' => 'They cleave DNA at or precisely within specific palindromic recognition sequences',
                            'option_c' => 'They possess simultaneous methylase and endonuclease domains on the same subunit',
                            'option_d' => 'They cleave randomly hundreds of base pairs downstream of the recognition site',
                            'correct_option' => 'B',
                            'explanation' => 'Type II restriction endonucleases are preferred in recombinant DNA technology because they cleave DNA symmetrically within or adjacent to palindromic sequences without requiring ATP.',
                            'marks' => 4,
                        ]
                    );

                    DppQuestion::firstOrCreate(
                        [
                            'dpp_id' => $dpp->id,
                            'question_number' => 2,
                        ],
                        [
                            'question_text' => 'In blue-white colony screening with pUC vectors, what causes recombinant bacterial colonies to appear white?',
                            'option_a' => 'Resistance to ampicillin is destroyed by insertion',
                            'option_b' => 'Insertional inactivation of the lacZ alpha-peptide prevents functional beta-galactosidase assembly',
                            'option_c' => 'Overproduction of IPTG causes toxic cell bleaching',
                            'option_d' => 'Chromogenic cleavage of X-Gal produces a permanent colorless precipitate',
                            'correct_option' => 'B',
                            'explanation' => 'Insertional inactivation of the lacZ gene prevents alpha-complementation, disabling X-Gal hydrolysis and leaving colonies white.',
                            'marks' => 4,
                        ]
                    );
                }
            }
        }
    }
}
