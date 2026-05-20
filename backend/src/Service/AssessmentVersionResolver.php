<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

class AssessmentVersionResolver
{
    private const OASIS_E2_CUTOFF = '2026-04-01';

    public function resolve(string $completedAt): string
    {
        $date = new DateTimeImmutable($completedAt);
        $cutoff = new DateTimeImmutable(self::OASIS_E2_CUTOFF . ' 00:00:00');

        return $date >= $cutoff ? 'OASIS-E2' : 'OASIS-E1';
    }
}
