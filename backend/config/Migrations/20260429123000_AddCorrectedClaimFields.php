<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCorrectedClaimFields extends BaseMigration
{
    public function up(): void
    {
        $claims = $this->table('claims');
        $hasChanges = false;

        if (!$claims->hasColumn('corrected_from_claim_id')) {
            $claims->addColumn('corrected_from_claim_id', 'integer', ['null' => true, 'after' => 'void_reason']);
            $hasChanges = true;
        }

        if (!$claims->hasColumn('correction_reason')) {
            $claims->addColumn('correction_reason', 'string', ['limit' => 255, 'null' => true, 'after' => 'corrected_from_claim_id']);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $claims->update();
        }
    }

    public function down(): void
    {
    }
}
