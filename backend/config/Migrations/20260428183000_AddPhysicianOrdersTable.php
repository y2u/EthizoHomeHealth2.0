<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPhysicianOrdersTable extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('physician_orders')) {
            return;
        }

        $this->table('physician_orders')
            ->addColumn('referral_id', 'integer', ['null' => true])
            ->addColumn('episode_id', 'integer')
            ->addColumn('referral_document_id', 'integer', ['null' => true])
            ->addColumn('order_scope', 'string', ['limit' => 60])
            ->addColumn('version_number', 'integer', ['default' => 1])
            ->addColumn('order_status', 'string', ['limit' => 40, 'default' => 'draft'])
            ->addColumn('active', 'boolean', ['default' => true])
            ->addColumn('sent_at', 'datetime', ['null' => true])
            ->addColumn('received_at', 'datetime', ['null' => true])
            ->addColumn('signed_at', 'datetime', ['null' => true])
            ->addColumn('signer_name', 'string', ['limit' => 160, 'null' => true])
            ->addColumn('order_summary', 'text', ['null' => true])
            ->addColumn('order_note', 'text', ['null' => true])
            ->addTimestamps('created', 'modified')
            ->addForeignKey('referral_id', 'referrals')
            ->addForeignKey('episode_id', 'episodes')
            ->addForeignKey('referral_document_id', 'referral_documents')
            ->create();
    }
}
