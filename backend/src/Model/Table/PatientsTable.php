<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PatientsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('patients');
        $this->addBehavior('Timestamp');
        $this->hasMany('Referrals');
        $this->hasMany('Episodes');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('first_name')->requirePresence('first_name', 'create')->notEmptyString('first_name')
            ->scalar('last_name')->requirePresence('last_name', 'create')->notEmptyString('last_name')
            ->date('dob')->requirePresence('dob', 'create')->notEmptyDate('dob')
            ->scalar('gender')
            ->requirePresence('gender', 'create')
            ->notEmptyString('gender')
            ->inList('gender', ['Female', 'Male', 'Non-binary', 'Other', 'Unknown'])
            ->scalar('payer_type')->requirePresence('payer_type', 'create')->notEmptyString('payer_type')
            ->scalar('phone')
            ->requirePresence('phone', 'create')
            ->notEmptyString('phone')
            ->regex('phone', '/^(?:\(\d{3}\) \d{3}-\d{4}|\d{3}-\d{3}-\d{4})$/', 'Use a valid US phone format.')
            ->scalar('address1')
            ->requirePresence('address1', 'create')
            ->notEmptyString('address1')
            ->scalar('city')
            ->requirePresence('city', 'create')
            ->notEmptyString('city')
            ->scalar('state')
            ->requirePresence('state', 'create')
            ->notEmptyString('state')
            ->minLength('state', 2, 'Use the 2-letter US state code.')
            ->maxLength('state', 2, 'Use the 2-letter US state code.')
            ->regex('state', '/^[A-Z]{2}$/', 'Use the 2-letter US state code.')
            ->scalar('postal_code')
            ->requirePresence('postal_code', 'create')
            ->notEmptyString('postal_code')
            ->regex('postal_code', '/^\d{5}(?:-\d{4})?$/', 'Use a valid US ZIP code.')
            ->scalar('insurance_member_id')
            ->maxLength('insurance_member_id', 60)
            ->requirePresence('insurance_member_id', function (array $context): bool {
                $payerType = (string)($context['data']['payer_type'] ?? '');

                return !in_array($payerType, ['Private Pay', 'Medicare', 'Medicare Advantage'], true);
            })
            ->notEmptyString('insurance_member_id', 'Insurance member ID is required for this payer type.', function (array $context): bool {
                $payerType = (string)($context['data']['payer_type'] ?? '');

                return !in_array($payerType, ['Private Pay', 'Medicare', 'Medicare Advantage'], true);
            })
            ->allowEmptyString('insurance_member_id')
            ->scalar('medicare_number')
            ->maxLength('medicare_number', 40)
            ->requirePresence('medicare_number', function (array $context): bool {
                $payerType = (string)($context['data']['payer_type'] ?? '');

                return in_array($payerType, ['Medicare', 'Medicare Advantage'], true);
            })
            ->notEmptyString('medicare_number', 'Medicare number is required for Medicare payers.', function (array $context): bool {
                $payerType = (string)($context['data']['payer_type'] ?? '');

                return in_array($payerType, ['Medicare', 'Medicare Advantage'], true);
            })
            ->allowEmptyString('medicare_number')
            ->scalar('emergency_contact_name')
            ->maxLength('emergency_contact_name', 120)
            ->allowEmptyString('emergency_contact_name')
            ->scalar('emergency_contact_relationship')
            ->maxLength('emergency_contact_relationship', 80)
            ->allowEmptyString('emergency_contact_relationship')
            ->scalar('emergency_contact_phone')
            ->allowEmptyString('emergency_contact_phone')
            ->regex('emergency_contact_phone', '/^(?:\(\d{3}\) \d{3}-\d{4}|\d{3}-\d{3}-\d{4})$/', 'Use a valid US phone format.')
            ->scalar('responsible_party_name')
            ->maxLength('responsible_party_name', 120)
            ->allowEmptyString('responsible_party_name')
            ->scalar('responsible_party_relationship')
            ->maxLength('responsible_party_relationship', 80)
            ->allowEmptyString('responsible_party_relationship')
            ->scalar('responsible_party_phone')
            ->allowEmptyString('responsible_party_phone')
            ->regex('responsible_party_phone', '/^(?:\(\d{3}\) \d{3}-\d{4}|\d{3}-\d{3}-\d{4})$/', 'Use a valid US phone format.')
            ->scalar('status')->allowEmptyString('status');

        return $validator;
    }
}
