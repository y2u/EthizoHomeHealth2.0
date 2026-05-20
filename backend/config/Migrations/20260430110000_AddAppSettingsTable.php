<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAppSettingsTable extends BaseMigration
{
    public function change(): void
    {
        if (!$this->hasTable('app_settings')) {
            $this->table('app_settings')
                ->addColumn('require_mfa', 'boolean', ['default' => false])
                ->addColumn('session_timeout_minutes', 'integer', ['default' => 30])
                ->addColumn('remember_device_days', 'integer', ['default' => 14])
                ->addColumn('password_rotation_days', 'integer', ['default' => 90])
                ->addColumn('attachment_retention_days', 'integer', ['default' => 365])
                ->addColumn('allowed_ip_ranges', 'text', ['null' => true])
                ->addColumn('enforce_device_attestation', 'boolean', ['default' => false])
                ->addTimestamps('created', 'modified')
                ->create();
        }
    }
}
