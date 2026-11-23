<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\AcademySetting;
use App\Services\SchoolBrandingService;

class PdfHandoutGenerator
{
    /**
     * Generate a valid, standard PDF 1.4 document for a course lesson handout.
     */
    public static function generate(Course $course, CourseLesson $lesson): string
    {
        $title = $course->title;
        $code = $course->code;
        $category = $course->category ?? 'Academic Study';
        $lessonNumber = $lesson->lesson_number;
        $lessonTitle = $lesson->title;
        $weekNumber = $lesson->week_number ?? 1;
        $duration = $lesson->duration_minutes ?? 60;
        $level = ucfirst($lesson->level ?? 'Standard');
        $summary = $lesson->topic_summary ?? 'Comprehensive lecture notes, problem sets, and key study guidelines.';
        $homework = $lesson->homework_task ?? 'Complete the assigned chapter exercises and practice problem set.';

        // Clean strings for PDF Type1 font (ASCII safe)
        $clean = function (string $str): string {
            $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            $str = str_replace(["\r\n", "\r", "\n"], ' ', $str);
            return preg_replace('/[^\x20-\x7E]/', '', $str);
        };

        $safeCourseTitle = substr($clean($title), 0, 55);
        $safeLessonTitle = substr($clean($lessonTitle), 0, 50);
        $safeSummary = substr($clean($summary), 0, 200);
        $safeHomework = substr($clean($homework), 0, 160);

        // Escape PDF syntax characters
        $pdfEscape = function (string $text): string {
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        };

        $school = SchoolBrandingService::get();
        $academicSession = AcademySetting::get('academic_session');
        $schoolName = strtoupper($clean($school['name'] ?? 'PRIMEOS ACADEMY'));
        $schoolDomain = $clean($school['website'] ?? 'www.primeos.edu');

        // Construct PostScript-like PDF Drawing & Text Stream
        $stream = "";

        // 1. Header Banner Background (Deep Teal / Emerald: rgb 0.06, 0.45, 0.42)
        $stream .= "q 0.06 0.45 0.42 rg 36 746 523 60 re f Q\n";

        // Header Title (White)
        $stream .= "BT /F1 16 Tf 1 1 1 rg 50 782 Td (" . $pdfEscape($schoolName) . ") Tj ET\n";
        $stream .= "BT /F2 9 Tf 0.85 0.95 0.93 rg 50 766 Td (" . $pdfEscape("OFFICIAL CURRICULUM STUDY HANDOUT & STRUCTURED NOTES") . ") Tj ET\n";
        $stream .= "BT /F1 10 Tf 1 1 1 rg 420 782 Td (" . $pdfEscape($code) . ") Tj ET\n";
        if ($academicSession) {
            $stream .= "BT /F2 8 Tf 0.85 0.95 0.93 rg 420 766 Td (" . $pdfEscape("Academic Session {$academicSession}") . ") Tj ET\n";
        }

        // 2. Course & Lesson Metadata Bar (Light Background: rgb 0.96, 0.97, 0.98)
        $stream .= "q 0.94 0.96 0.97 rg 36 676 523 58 re f Q\n";
        $stream .= "q 0.80 0.85 0.88 RG 1 w 36 676 523 58 re S Q\n";

        $stream .= "BT /F1 12 Tf 0.1 0.15 0.2 rg 50 714 Td (" . $pdfEscape($safeCourseTitle) . ") Tj ET\n";
        $stream .= "BT /F1 10 Tf 0.06 0.45 0.42 rg 50 698 Td (" . $pdfEscape("Lesson {$lessonNumber}: {$safeLessonTitle}") . ") Tj ET\n";
        $stream .= "BT /F2 8 Tf 0.4 0.45 0.5 rg 50 684 Td (" . $pdfEscape("Category: {$category}   |   Week: {$weekNumber}   |   Duration: {$duration} Mins   |   Track: {$level}") . ") Tj ET\n";

        // 3. Section: Academic Lecture Summary & Objectives
        $stream .= "BT /F1 11 Tf 0.06 0.45 0.42 rg 50 644 Td (" . $pdfEscape("1. EXECUTIVE LECTURE OVERVIEW") . ") Tj ET\n";
        $stream .= "q 0.06 0.45 0.42 RG 1.5 w 50 638 m 220 638 l S Q\n";

        // Wrap summary in 2 lines
        $sumPart1 = substr($safeSummary, 0, 95);
        $sumPart2 = substr($safeSummary, 95, 95);
        $stream .= "BT /F2 9.5 Tf 0.2 0.25 0.3 rg 50 620 Td (" . $pdfEscape($sumPart1) . ") Tj ET\n";
        if ($sumPart2) {
            $stream .= "BT /F2 9.5 Tf 0.2 0.25 0.3 rg 50 606 Td (" . $pdfEscape($sumPart2) . ") Tj ET\n";
        }

        // Objectives Box
        $stream .= "q 0.98 0.99 1.0 rg 50 514 495 76 re f Q\n";
        $stream .= "q 0.85 0.90 0.94 RG 0.75 w 50 514 495 76 re S Q\n";
        $stream .= "BT /F1 9 Tf 0.15 0.3 0.5 rg 60 572 Td (" . $pdfEscape("Core Learning Objectives & Competencies:") . ") Tj ET\n";

        $objectives = is_array($lesson->learning_objectives) ? $lesson->learning_objectives : [
            "Master foundational theory and systematic problem solving methodologies.",
            "Analyze analytical problem sets and avoid common misconceptions.",
            "Execute and verify practical applications under benchmark conditions.",
        ];

        $yObj = 554;
        foreach (array_slice($objectives, 0, 3) as $idx => $obj) {
            $safeObj = substr($clean($obj), 0, 95);
            $stream .= "BT /F2 8.5 Tf 0.25 0.3 0.35 rg 65 {$yObj} Td (" . $pdfEscape("[*]  " . $safeObj) . ") Tj ET\n";
            $yObj -= 14;
        }

        // 4. Section: Structured Study Guide & Essential Concepts
        $stream .= "BT /F1 11 Tf 0.06 0.45 0.42 rg 50 484 Td (" . $pdfEscape("2. STRUCTURED STUDY NOTES & SYSTEMATIC FORMULAS") . ") Tj ET\n";
        $stream .= "q 0.06 0.45 0.42 RG 1.5 w 50 478 m 330 478 l S Q\n";

        // Concept 1 Box
        $stream .= "q 0.98 0.98 0.98 rg 50 398 238 68 re f Q\n";
        $stream .= "q 0.85 0.85 0.85 RG 0.5 w 50 398 238 68 re S Q\n";
        $stream .= "BT /F1 9 Tf 0.1 0.1 0.1 rg 60 450 Td (" . $pdfEscape("Theoretical Axiom & Model") . ") Tj ET\n";
        $stream .= "BT /F2 8 Tf 0.3 0.35 0.4 rg 60 436 Td (" . $pdfEscape("Foundational principles governing this session.") . ") Tj ET\n";
        $stream .= "BT /F2 8 Tf 0.3 0.35 0.4 rg 60 422 Td (" . $pdfEscape("Validate boundary constraints before execution.") . ") Tj ET\n";
        $stream .= "BT /F1 7.5 Tf 0.06 0.45 0.42 rg 60 408 Td (" . $pdfEscape("Rule: Ensure strict verification criteria.") . ") Tj ET\n";

        // Concept 2 Box
        $stream .= "q 0.98 0.98 0.98 rg 307 398 238 68 re f Q\n";
        $stream .= "q 0.85 0.85 0.85 RG 0.5 w 307 398 238 68 re S Q\n";
        $stream .= "BT /F1 9 Tf 0.1 0.1 0.1 rg 317 450 Td (" . $pdfEscape("Analytical Verification Pattern") . ") Tj ET\n";
        $stream .= "BT /F2 8 Tf 0.3 0.35 0.4 rg 317 436 Td (" . $pdfEscape("Deconstruct complex challenges into modular steps.") . ") Tj ET\n";
        $stream .= "BT /F2 8 Tf 0.3 0.35 0.4 rg 317 422 Td (" . $pdfEscape("Apply invariant properties consistently.") . ") Tj ET\n";
        $stream .= "BT /F1 7.5 Tf 0.06 0.45 0.42 rg 317 408 Td (" . $pdfEscape("Pattern: Validate input -> Compute -> Verify.") . ") Tj ET\n";

        // 5. Section: Daily Practice Problem (DPP) & Homework
        $stream .= "BT /F1 11 Tf 0.06 0.45 0.42 rg 50 364 Td (" . $pdfEscape("3. DAILY PRACTICE (DPP) & HOMEWORK TASK") . ") Tj ET\n";
        $stream .= "q 0.06 0.45 0.42 RG 1.5 w 50 358 m 310 358 l S Q\n";

        // Homework Card
        $stream .= "q 0.97 0.98 0.95 rg 50 280 495 66 re f Q\n";
        $stream .= "q 0.82 0.88 0.80 RG 0.75 w 50 280 495 66 re S Q\n";
        $stream .= "BT /F1 9 Tf 0.2 0.4 0.2 rg 60 330 Td (" . $pdfEscape("Mandatory Student Assignment Task:") . ") Tj ET\n";

        $hwPart1 = substr($safeHomework, 0, 95);
        $hwPart2 = substr($safeHomework, 95, 95);
        $stream .= "BT /F2 8.5 Tf 0.25 0.25 0.25 rg 60 314 Td (" . $pdfEscape($hwPart1) . ") Tj ET\n";
        if ($hwPart2) {
            $stream .= "BT /F2 8.5 Tf 0.25 0.25 0.25 rg 60 300 Td (" . $pdfEscape($hwPart2) . ") Tj ET\n";
        }
        $stream .= "BT /F1 8 Tf 0.4 0.2 0.1 rg 60 288 Td (" . $pdfEscape("Submission Deadline: Sunday 23:59 via Portal or Faculty Desk") . ") Tj ET\n";

        // 6. Section: Verification & Grading Rubric
        $stream .= "BT /F1 11 Tf 0.06 0.45 0.42 rg 50 248 Td (" . $pdfEscape("4. EVALUATION & ACCREDITATION STANDARDS") . ") Tj ET\n";
        $stream .= "q 0.06 0.45 0.42 RG 1.5 w 50 242 m 310 242 l S Q\n";

        $stream .= "BT /F2 8.5 Tf 0.3 0.35 0.4 rg 50 224 Td (" . $pdfEscape("1. Logic Accuracy (40%): Correct derivations, proper notation, and valid step-by-step reasoning.") . ") Tj ET\n";
        $stream .= "BT /F2 8.5 Tf 0.3 0.35 0.4 rg 50 210 Td (" . $pdfEscape("2. Methodological Rigor (30%): Systematic implementation of framework patterns and constraints.") . ") Tj ET\n";
        $stream .= "BT /F2 8.5 Tf 0.3 0.35 0.4 rg 50 196 Td (" . $pdfEscape("3. Synthesis & Edge Cases (30%): Demonstration of deep understanding across non-trivial boundary states.") . ") Tj ET\n";

        // 7. Official Seal & Signature Placeholder
        $stream .= "q 0.95 0.95 0.95 rg 50 114 495 62 re f Q\n";
        $stream .= "q 0.85 0.85 0.85 RG 0.5 w 50 114 495 62 re S Q\n";
        $stream .= "BT /F1 8 Tf 0.2 0.2 0.2 rg 60 158 Td (" . $pdfEscape("ACADEMIC COUNCIL VERIFICATION & INSTITUTIONAL SEAL") . ") Tj ET\n";
        $stream .= "BT /F2 7.5 Tf 0.4 0.4 0.4 rg 60 144 Td (" . $pdfEscape("Authenticated for {$schoolName} LMS & Distribution. Powered by PrimeOS.") . ") Tj ET\n";
        $stream .= "BT /F2 7.5 Tf 0.4 0.4 0.4 rg 60 132 Td (" . $pdfEscape("Document ID: POS-DOC-" . str_pad((string)$course->id, 3, '0', STR_PAD_LEFT) . "-L" . str_pad((string)$lessonNumber, 3, '0', STR_PAD_LEFT) . "   |   Generated: " . date('Y-m-d H:i') . " UTC") . ") Tj ET\n";
        $stream .= "BT /F1 8 Tf 0.06 0.45 0.42 rg 400 132 Td (" . $pdfEscape("[OFFICIAL SEAL]") . ") Tj ET\n";

        // 8. Footer Line
        $stream .= "q 0.8 0.8 0.8 RG 0.5 w 36 60 523 0 re S Q\n";
        $stream .= "BT /F2 7 Tf 0.5 0.5 0.5 rg 50 48 Td (" . $pdfEscape("{$schoolName} Handout System | Page 1 of 1 | Powered by PrimeOS") . ") Tj ET\n";
        $stream .= "BT /F1 7 Tf 0.06 0.45 0.42 rg 440 48 Td (" . $pdfEscape($schoolDomain) . ") Tj ET\n";

        // Assemble PDF objects and calculate cross-reference offsets
        $out = "%PDF-1.4\n";
        $offsets = [];

        $objs = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj",
            "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj",
            "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj",
        ];

        foreach ($objs as $i => $obj) {
            $offsets[$i + 1] = strlen($out);
            $out .= $obj . "\n";
        }

        $xrefOffset = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objs); $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $out .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\n";
        $out .= "startxref\n" . $xrefOffset . "\n%%EOF\n";

        return $out;
    }
}
