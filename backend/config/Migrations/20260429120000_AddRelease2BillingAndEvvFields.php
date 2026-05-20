<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddRelease2BillingAndEvvFields extends BaseMigration
{
    public function up(): void
    {
        $claims = $this->table('claims');
        $hasClaimChanges = false;
        if (!$claims->hasColumn('payer_claim_number')) {
            $claims->addColumn('payer_claim_number', 'string', ['limit' => 80, 'null' => true, 'after' => 'submission_reference']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('accepted_at')) {
            $claims->addColumn('accepted_at', 'datetime', ['null' => true, 'after' => 'submitted_at']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('rejected_at')) {
            $claims->addColumn('rejected_at', 'datetime', ['null' => true, 'after' => 'accepted_at']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('rejection_reason')) {
            $claims->addColumn('rejection_reason', 'string', ['limit' => 255, 'null' => true, 'after' => 'rejected_at']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('payment_amount')) {
            $claims->addColumn('payment_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'after' => 'rejection_reason']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('remittance_reference')) {
            $claims->addColumn('remittance_reference', 'string', ['limit' => 80, 'null' => true, 'after' => 'payment_amount']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('voided_at')) {
            $claims->addColumn('voided_at', 'datetime', ['null' => true, 'after' => 'paid_at']);
            $hasClaimChanges = true;
        }
        if (!$claims->hasColumn('void_reason')) {
            $claims->addColumn('void_reason', 'string', ['limit' => 255, 'null' => true, 'after' => 'voided_at']);
            $hasClaimChanges = true;
        }
        if ($hasClaimChanges) {
            $claims->update();
        }

        $evvRecords = $this->table('evv_records');
        if (!$evvRecords->hasColumn('submission_reference')) {
            $evvRecords
                ->addColumn('submission_reference', 'string', ['limit' => 80, 'null' => true, 'after' => 'submitted_at'])
                ->update();
        }
    }

    public function down(): void
    {
    }
}
