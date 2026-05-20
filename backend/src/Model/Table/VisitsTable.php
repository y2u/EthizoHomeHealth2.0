<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class VisitsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('visits');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Episodes');
        $this->hasMany('CheckInOutEvents');
        $this->hasMany('EvvRecords');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('episode_id')->requirePresence('episode_id', 'create')->notEmptyString('episode_id')
            ->integer('patient_id')->requirePresence('patient_id', 'create')->notEmptyString('patient_id')
            ->scalar('visit_type')->requirePresence('visit_type', 'create')->notEmptyString('visit_type')
            ->scalar('discipline')->requirePresence('discipline', 'create')->notEmptyString('discipline')
            ->dateTime('scheduled_start')->requirePresence('scheduled_start', 'create')->notEmptyDateTime('scheduled_start')
            ->dateTime('scheduled_end')->requirePresence('scheduled_end', 'create')->notEmptyDateTime('scheduled_end')
            ->scalar('clinician_name')->requirePresence('clinician_name', 'create')->notEmptyString('clinician_name')
            ->scalar('documentation_status')->allowEmptyString('documentation_status')
            ->scalar('documentation_payload')->allowEmptyString('documentation_payload')
            ->scalar('qa_review_notes')->allowEmptyString('qa_review_notes')
            ->scalar('reassigned_from_clinician')->allowEmptyString('reassigned_from_clinician')
            ->scalar('missed_reason')->allowEmptyString('missed_reason')
            ->scalar('follow_up_plan')->allowEmptyString('follow_up_plan')
            ->boolean('requires_evv');

        return $validator;
    }
}
