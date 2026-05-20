<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddVisitDocumentationFields extends BaseMigration
{
    public function change(): void
    {
        $visits = $this->table('visits');

        if (!$visits->hasColumn('documentation_payload')) {
            $visits->addColumn('documentation_payload', 'text', ['null' => true]);
        }

        if (!$visits->hasColumn('qa_review_notes')) {
            $visits->addColumn('qa_review_notes', 'text', ['null' => true]);
        }

        $visits->update();
    }
}
