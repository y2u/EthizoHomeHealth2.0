<?php
declare(strict_types=1);

namespace App\Service;

class PdgmGrouper
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function group(array $context): array
    {
        $diagnosis = strtoupper((string)($context['principal_diagnosis_code'] ?? 'R69'));
        $functionalScore = (int)($context['functional_score'] ?? 0);
        $timing = (($context['period_number'] ?? 1) > 1) ? 'LATE' : 'EARLY';
        $comorbidity = strtoupper((string)($context['comorbidity_level'] ?? 'NONE'));
        $admissionSource = strtoupper((string)($context['admission_source'] ?? 'COMMUNITY'));

        $clinicalGroup = match (true) {
            str_starts_with($diagnosis, 'I') => 'MMTA-CARDIAC',
            str_starts_with($diagnosis, 'J') => 'MMTA-RESPIRATORY',
            str_starts_with($diagnosis, 'M') => 'MUSCULOSKELETAL',
            default => 'MMTA-OTHER',
        };

        $functionalTier = match (true) {
            $functionalScore >= 16 => 'HIGH',
            $functionalScore >= 8 => 'MEDIUM',
            default => 'LOW',
        };

        return [
            'clinical_group' => $clinicalGroup,
            'timing' => $timing,
            'functional_level' => $functionalTier,
            'comorbidity_adjustment' => $comorbidity,
            'admission_source' => $admissionSource,
            'group_code' => implode('-', [$clinicalGroup, $admissionSource, $timing, $functionalTier, $comorbidity]),
        ];
    }
}
