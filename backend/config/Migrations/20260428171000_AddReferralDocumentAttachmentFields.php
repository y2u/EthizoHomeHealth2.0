<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReferralDocumentAttachmentFields extends BaseMigration
{
    public function change(): void
    {
        $documents = $this->table('referral_documents');

        if (!$documents->hasColumn('original_file_name')) {
            $documents->addColumn('original_file_name', 'string', ['limit' => 255, 'null' => true]);
        }
        if (!$documents->hasColumn('stored_file_name')) {
            $documents->addColumn('stored_file_name', 'string', ['limit' => 255, 'null' => true]);
        }
        if (!$documents->hasColumn('mime_type')) {
            $documents->addColumn('mime_type', 'string', ['limit' => 120, 'null' => true]);
        }
        if (!$documents->hasColumn('file_size')) {
            $documents->addColumn('file_size', 'integer', ['null' => true]);
        }
        if (!$documents->hasColumn('attachment_path')) {
            $documents->addColumn('attachment_path', 'string', ['limit' => 255, 'null' => true]);
        }

        $documents->update();
    }
}
