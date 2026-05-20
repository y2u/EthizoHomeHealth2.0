<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddVisitOperationsFields extends BaseMigration
{
    public function change(): void
    {
        $visits = $this->table('visits');

        if (!$visits->hasColumn('documentation_status')) {
            $visits->addColumn('documentation_status', 'string', [
                'limit' => 40,
                'default' => 'pending',
            ]);
        }

        if (!$visits->hasColumn('reassigned_from_clinician')) {
            $visits->addColumn('reassigned_from_clinician', 'string', [
                'limit' => 160,
                'null' => true,
            ]);
        }

        if (!$visits->hasColumn('missed_reason')) {
            $visits->addColumn('missed_reason', 'text', ['null' => true]);
        }

        if (!$visits->hasColumn('follow_up_plan')) {
            $visits->addColumn('follow_up_plan', 'text', ['null' => true]);
        }

        $visits->update();
    }
}
