<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserAccessFields extends BaseMigration
{
    public function change(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $users = $this->table('users');

        if (!$users->hasColumn('status')) {
            $users->addColumn('status', 'string', [
                'limit' => 30,
                'default' => 'active',
                'after' => 'mobile',
            ]);
        }
        if (!$users->hasColumn('mfa_enabled')) {
            $users->addColumn('mfa_enabled', 'boolean', [
                'default' => false,
                'after' => 'status',
            ]);
        }

        $users->update();
    }
}
