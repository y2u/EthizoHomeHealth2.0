<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReferralDocumentsTable extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('referral_documents')) {
            return;
        }

        $this->table('referral_documents')
            ->addColumn('referral_id', 'integer')
            ->addColumn('document_type', 'string', ['limit' => 60])
            ->addColumn('document_status', 'string', ['limit' => 40, 'default' => 'requested'])
            ->addColumn('source_name', 'string', ['limit' => 160, 'null' => true])
            ->addColumn('received_at', 'datetime', ['null' => true])
            ->addColumn('signed_at', 'datetime', ['null' => true])
            ->addColumn('document_note', 'text', ['null' => true])
            ->addTimestamps('created', 'modified')
            ->addForeignKey('referral_id', 'referrals')
            ->create();
    }
}
