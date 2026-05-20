<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddQaTaskEscalationFields extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('qa_tasks');
        if (!$table->hasColumn('assignment_history')) {
            $table->addColumn('assignment_history', 'text', ['null' => true]);
        }
        if (!$table->hasColumn('escalation_note')) {
            $table->addColumn('escalation_note', 'text', ['null' => true]);
        }
        if (!$table->hasColumn('last_escalated_at')) {
            $table->addColumn('last_escalated_at', 'datetime', ['null' => true]);
        }
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('qa_tasks');
        if ($table->hasColumn('last_escalated_at')) {
            $table->removeColumn('last_escalated_at');
        }
        if ($table->hasColumn('escalation_note')) {
            $table->removeColumn('escalation_note');
        }
        if ($table->hasColumn('assignment_history')) {
            $table->removeColumn('assignment_history');
        }
        $table->update();
    }
}
