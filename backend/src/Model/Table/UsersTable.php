<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('full_name')->maxLength('full_name', 120)->requirePresence('full_name', 'create')->notEmptyString('full_name')
            ->email('email')->requirePresence('email', 'create')->notEmptyString('email')
            ->scalar('password_hash')->requirePresence('password_hash', 'create')->notEmptyString('password_hash')
            ->scalar('role')->requirePresence('role', 'create')->notEmptyString('role')
            ->scalar('status')->allowEmptyString('status')
            ->boolean('mfa_enabled')
            ->allowEmptyString('mobile');

        return $validator;
    }
}
