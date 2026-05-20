<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddQaTaskAssignmentFields extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('qa_tasks');
        if (!$table->hasColumn('assigned_user_name')) {
            $table->addColumn('assigned_user_name', 'string', ['limit' => 120, 'null' => true]);
        }
        if (!$table->hasColumn('assigned_at')) {
            $table->addColumn('assigned_at', 'datetime', ['null' => true]);
        }
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('qa_tasks');
        if ($table->hasColumn('assigned_at')) {
            $table->removeColumn('assigned_at');
        }
        if ($table->hasColumn('assigned_user_name')) {
            $table->removeColumn('assigned_user_name');
        }
        $table->update();
    }
}
