<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ReferralsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('referrals');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Patients');
        $this->hasMany('Episodes');
        $this->hasMany('ReferralDocuments');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('patient_id')->requirePresence('patient_id', 'create')->notEmptyString('patient_id')
            ->scalar('source_name')->requirePresence('source_name', 'create')->notEmptyString('source_name')
            ->scalar('admission_source')->requirePresence('admission_source', 'create')->notEmptyString('admission_source')
            ->scalar('payer_type')->requirePresence('payer_type', 'create')->notEmptyString('payer_type')
            ->scalar('primary_diagnosis')->requirePresence('primary_diagnosis', 'create')->notEmptyString('primary_diagnosis')
            ->date('planned_soc_date')->requirePresence('planned_soc_date', 'create')->notEmptyDate('planned_soc_date')
            ->date('face_to_face_date')->requirePresence('face_to_face_date', 'create')->notEmptyDate('face_to_face_date')
            ->scalar('order_status')->requirePresence('order_status', 'create')->notEmptyString('order_status')
            ->boolean('physician_orders_signed')->requirePresence('physician_orders_signed', 'create')
            ->dateTime('physician_orders_signed_at')->allowEmptyDateTime('physician_orders_signed_at')
            ->scalar('referring_provider_name')->requirePresence('referring_provider_name', 'create')->notEmptyString('referring_provider_name')
            ->scalar('referring_provider_phone')->requirePresence('referring_provider_phone', 'create')->notEmptyString('referring_provider_phone')
            ->regex('referring_provider_phone', '/^(?:\(\d{3}\) \d{3}-\d{4}|\d{3}-\d{3}-\d{4})$/', 'Use a valid US phone format.')
            ->scalar('pcp_name')->allowEmptyString('pcp_name')
            ->scalar('pcp_phone')->allowEmptyString('pcp_phone')
            ->regex('pcp_phone', '/^(?:\(\d{3}\) \d{3}-\d{4}|\d{3}-\d{3}-\d{4})$/', 'Use a valid US phone format.')
            ->scalar('caregiver_name')->requirePresence('caregiver_name', 'create')->notEmptyString('caregiver_name')
            ->scalar('caregiver_relationship')->requirePresence('caregiver_relationship', 'create')->notEmptyString('caregiver_relationship')
            ->scalar('caregiver_phone')->requirePresence('caregiver_phone', 'create')->notEmptyString('caregiver_phone')
            ->regex('caregiver_phone', '/^(?:\(\d{3}\) \d{3}-\d{4}|\d{3}-\d{3}-\d{4})$/', 'Use a valid US phone format.')
            ->scalar('service_location_type')->requirePresence('service_location_type', 'create')->notEmptyString('service_location_type')
            ->scalar('service_address1')->requirePresence('service_address1', 'create')->notEmptyString('service_address1')
            ->scalar('service_city')->requirePresence('service_city', 'create')->notEmptyString('service_city')
            ->scalar('service_state')->requirePresence('service_state', 'create')->notEmptyString('service_state')
            ->minLength('service_state', 2, 'Use the 2-letter US state code.')
            ->maxLength('service_state', 2, 'Use the 2-letter US state code.')
            ->regex('service_state', '/^[A-Z]{2}$/', 'Use the 2-letter US state code.')
            ->scalar('service_postal_code')->requirePresence('service_postal_code', 'create')->notEmptyString('service_postal_code')
            ->regex('service_postal_code', '/^\d{5}(?:-\d{4})?$/', 'Use a valid US ZIP code.');

        $validator->add('physician_orders_signed_at', 'signedTimestampRequired', [
            'rule' => function ($value, array $context): bool {
                if (($context['data']['physician_orders_signed'] ?? false) !== true) {
                    return true;
                }

                return !empty($value);
            },
            'message' => 'Signed orders date/time is required when physician orders are marked signed.',
        ]);

        return $validator;
    }
}
