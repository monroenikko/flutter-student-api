<?php

namespace App\Traits;

trait FormatsSubjectTitle
{
    /**
     * Format a subject or sub-subject title to proper Title Case while preserving
     * acronyms (e.g., MAPEH, TLE, ICT, ESP, TVL, SMAW) and Roman numerals (e.g., I, II, III)
     * in ALL CAPS (even inside parentheses like (TLE)) and preserving small
     * conjunctions/prepositions (and, of, ng, sa, na, at) in lowercase.
     *
     * @param string|null $str
     * @return string
     */
    public function formatSubjectTitle(?string $str): string
    {
        return self::formatTitle($str);
    }

    /**
     * Static helper to format subject title.
     *
     * @param string|null $str
     * @return string
     */
    public static function formatTitle(?string $str): string
    {
        if ($str === null || trim($str) === '') {
            return '';
        }

        $str = trim($str);

        $acronyms = [
            'MAPEH', 'TLE', 'ICT', 'ESP', 'TVL', 'SMAW', 'EPP', 'GMRC', 'ALS',
            'STEM', 'ABM', 'HUMSS', 'GAS', 'HE', 'IA', 'AFA', 'CSS', 'FBS',
            'BPP', 'EIM', 'EPAS', 'AP', 'DRRR', 'PE', 'SHS', 'JHS', 'NSTP',
            'CWTS', 'ROTC', 'LTS', 'CPAR', 'UCSP', 'MIL', 'DISS', 'DIASS',
            'PPG', 'OJT', 'NC', 'IT', 'HOPE', 'P.E.', 'P.E'
        ];
        $acronymLookup = array_change_key_case(array_flip($acronyms), CASE_UPPER);

        $smallWords = [
            'and', 'or', 'of', 'in', 'on', 'at', 'to', 'for', 'with', 'by',
            'from', 'as', 'ng', 'sa', 'mga', 'na', 'at', 'ay', 'nang', 'the', 'an', 'a'
        ];
        $smallWordsLookup = array_change_key_case(array_flip($smallWords), CASE_LOWER);

        $romanRegex = '/^(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII|XIII|XIV|XV|XVI|XVII|XVIII|XIX|XX)$/i';

        $words = preg_split('/\s+/', $str);
        $formattedWords = [];

        foreach ($words as $idx => $word) {
            if ($word === '' || $word === '-' || $word === '/' || $word === '&') {
                $formattedWords[] = $word;
                continue;
            }

            if (preg_match('/^([^a-zA-Z0-9]*)(.*?)([^a-zA-Z0-9]*)$/u', $word, $matches)) {
                $leading = $matches[1] ?? '';
                $core = $matches[2] ?? '';
                $trailing = $matches[3] ?? '';

                if ($core === '') {
                    $formattedWords[] = $word;
                    continue;
                }

                $subparts = preg_split('/([-\/])/', $core, -1, PREG_SPLIT_DELIM_CAPTURE);
                $formattedSubparts = [];

                foreach ($subparts as $sIdx => $part) {
                    if ($part === '-' || $part === '/') {
                        $formattedSubparts[] = $part;
                        continue;
                    }

                    $upperPart = mb_strtoupper($part, 'UTF-8');
                    $lowerPart = mb_strtolower($part, 'UTF-8');

                    // 1. Acronym check
                    if (isset($acronymLookup[$upperPart])) {
                        $formattedSubparts[] = $upperPart;
                    }
                    // 2. Roman numeral check
                    elseif (preg_match($romanRegex, $part)) {
                        $formattedSubparts[] = $upperPart;
                    }
                    // 3. Small word check
                    elseif (isset($smallWordsLookup[$lowerPart])) {
                        if ($idx === 0 && $sIdx === 0) {
                            $formattedSubparts[] = ucfirst($lowerPart);
                        } else {
                            $formattedSubparts[] = $lowerPart;
                        }
                    }
                    // 4. Regular word -> Title Case
                    else {
                        $formattedSubparts[] = ucfirst($lowerPart);
                    }
                }

                $formattedWords[] = $leading . implode('', $formattedSubparts) . $trailing;
            } else {
                $formattedWords[] = $word;
            }
        }

        return implode(' ', $formattedWords);
    }
}
