<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AppSettingsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('app_settings');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->boolean('require_mfa')
            ->boolean('enforce_device_attestation')
            ->integer('session_timeout_minutes')
            ->greaterThanOrEqual('session_timeout_minutes', 5)
            ->integer('remember_device_days')
            ->greaterThanOrEqual('remember_device_days', 0)
            ->integer('password_rotation_days')
            ->greaterThanOrEqual('password_rotation_days', 0)
            ->integer('attachment_retention_days')
            ->greaterThanOrEqual('attachment_retention_days', 30)
            ->allowEmptyString('allowed_ip_ranges');

        return $validator;
    }
}
