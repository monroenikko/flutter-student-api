<?php

namespace Tests\Unit;

use App\Traits\FormatsSubjectTitle;
use Tests\TestCase;

class FormatsSubjectTitleTest extends TestCase
{
    use FormatsSubjectTitle;

    public function test_formats_acronyms_in_all_caps(): void
    {
        $this->assertEquals('MAPEH', $this->formatSubjectTitle('MAPEH'));
        $this->assertEquals('MAPEH', $this->formatSubjectTitle('mapeh'));
        $this->assertEquals('TLE', $this->formatSubjectTitle('tle'));
        $this->assertEquals('ICT', $this->formatSubjectTitle('ict'));
        $this->assertEquals('ESP', $this->formatSubjectTitle('esp'));
        $this->assertEquals('TVL', $this->formatSubjectTitle('tvl'));
        $this->assertEquals('SMAW', $this->formatSubjectTitle('smaw'));
        $this->assertEquals('EPP', $this->formatSubjectTitle('epp'));
        $this->assertEquals('GMRC', $this->formatSubjectTitle('gmrc'));
        $this->assertEquals('STEM', $this->formatSubjectTitle('stem'));
        $this->assertEquals('HOPE 1', $this->formatSubjectTitle('hope 1'));
    }

    public function test_formats_acronyms_inside_parentheses(): void
    {
        $this->assertEquals('(TLE)', $this->formatSubjectTitle('(TLE)'));
        $this->assertEquals('(TLE)', $this->formatSubjectTitle('(tle)'));
        $this->assertEquals('(ESP)', $this->formatSubjectTitle('(esp)'));
        $this->assertEquals('(ICT)', $this->formatSubjectTitle('(ict)'));
        $this->assertEquals('(MAPEH)', $this->formatSubjectTitle('(mapeh)'));
        $this->assertEquals('(SMAW)', $this->formatSubjectTitle('(smaw)'));
        $this->assertEquals('(EPP)', $this->formatSubjectTitle('(epp)'));
    }

    public function test_formats_roman_numerals(): void
    {
        $this->assertEquals('Research I', $this->formatSubjectTitle('RESEARCH I'));
        $this->assertEquals('Research II', $this->formatSubjectTitle('research ii'));
        $this->assertEquals('Research III', $this->formatSubjectTitle('Research iii'));
        $this->assertEquals('Research IV', $this->formatSubjectTitle('research iv'));
        $this->assertEquals('General Biology I', $this->formatSubjectTitle('GENERAL BIOLOGY I'));
        $this->assertEquals('Bread and Pastry Production (NC II)', $this->formatSubjectTitle('BREAD AND PASTRY PRODUCTION (NC II)'));
        $this->assertEquals('Bread and Pastry Production (NC II)', $this->formatSubjectTitle('bread and pastry production (nc ii)'));
    }

    public function test_preserves_small_words_in_lowercase(): void
    {
        $this->assertEquals(
            'Technology and Livelihood Education (TLE)',
            $this->formatSubjectTitle('TECHNOLOGY AND LIVELIHOOD EDUCATION (TLE)')
        );
        $this->assertEquals(
            'Technology and Livelihood Education (TLE)',
            $this->formatSubjectTitle('technology and livelihood education (tle)')
        );
        $this->assertEquals(
            'Edukasyon sa Pagpapakatao (ESP)',
            $this->formatSubjectTitle('EDUKASYON SA PAGPAPAKATAO (ESP)')
        );
        $this->assertEquals(
            'Edukasyon sa Pagpapakatao (ESP)',
            $this->formatSubjectTitle('edukasyon sa pagpapakatao (esp)')
        );
        $this->assertEquals(
            'Edukasyong Pantahanan at Pangkabuhayan (EPP)',
            $this->formatSubjectTitle('EDUKASYONG PANTAHANAN AT PANGKABUHAYAN (EPP)')
        );
        $this->assertEquals(
            'Araling Panlipunan',
            $this->formatSubjectTitle('ARALING PANLIPUNAN')
        );
        $this->assertEquals(
            'Araling Panlipunan',
            $this->formatSubjectTitle('araling panlipunan')
        );
        $this->assertEquals(
            'Music, Arts, Physical Education, and Health (MAPEH)',
            $this->formatSubjectTitle('MUSIC, ARTS, PHYSICAL EDUCATION, AND HEALTH (MAPEH)')
        );
        $this->assertEquals(
            'Disaster Readiness and Risk Reduction (DRRR)',
            $this->formatSubjectTitle('DISASTER READINESS AND RISK REDUCTION (DRRR)')
        );
        $this->assertEquals(
            'Contemporary Philippine Arts from the Regions',
            $this->formatSubjectTitle('CONTEMPORARY PHILIPPINE ARTS FROM THE REGIONS')
        );
    }

    public function test_formats_hyphenated_and_composite_titles(): void
    {
        $this->assertEquals('TVL - SMAW', $this->formatSubjectTitle('TVL - SMAW'));
        $this->assertEquals('TVL - SMAW', $this->formatSubjectTitle('tvl - smaw'));
        $this->assertEquals('TVL-SMAW', $this->formatSubjectTitle('tvl-smaw'));
        $this->assertEquals('SHS - TVL (ICT)', $this->formatSubjectTitle('shs - tvl (ict)'));
        $this->assertEquals('Hands-on Training', $this->formatSubjectTitle('HANDS-ON TRAINING'));
    }

    public function test_formats_first_word_even_if_small_word(): void
    {
        $this->assertEquals('Of Mice and Men', $this->formatSubjectTitle('of mice and men'));
        $this->assertEquals('And Justice for All', $this->formatSubjectTitle('and justice for all'));
    }

    public function test_handles_empty_and_null_inputs(): void
    {
        $this->assertEquals('', $this->formatSubjectTitle(null));
        $this->assertEquals('', $this->formatSubjectTitle(''));
        $this->assertEquals('', $this->formatSubjectTitle('   '));
    }
}
