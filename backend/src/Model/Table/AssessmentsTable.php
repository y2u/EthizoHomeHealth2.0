<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AssessmentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('assessments');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Episodes');
        $this->hasMany('QaTasks');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('episode_id')->requirePresence('episode_id', 'create')->notEmptyString('episode_id')
            ->scalar('assessment_type')->requirePresence('assessment_type', 'create')->notEmptyString('assessment_type')
            ->dateTime('completed_at')->requirePresence('completed_at', 'create')->notEmptyDateTime('completed_at')
            ->scalar('principal_diagnosis_code')->requirePresence('principal_diagnosis_code', 'create')->notEmptyString('principal_diagnosis_code')
            ->regex('principal_diagnosis_code', '/^[A-TV-Z][0-9][0-9A-Z](?:\.[0-9A-Z]{1,4})?$/', 'Use a valid ICD-10 diagnosis code format.')
            ->integer('functional_score')->requirePresence('functional_score', 'create')->notEmptyString('functional_score')
            ->scalar('status')->requirePresence('status', 'create')->notEmptyString('status')
            ->boolean('medication_reconciliation_completed')
            ->scalar('homebound_status')
            ->notEmptyString('homebound_status', 'Homebound status is required on finalized assessments.', function (array $context): bool {
                return in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->allowEmptyString('homebound_status', null, function (array $context): bool {
                return !in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->scalar('homebound_narrative')
            ->notEmptyString('homebound_narrative', 'Homebound narrative is required on finalized assessments.', function (array $context): bool {
                return in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->allowEmptyString('homebound_narrative', null, function (array $context): bool {
                return !in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->allowEmptyString('fall_risk_level')
            ->allowEmptyString('hospitalization_risk')
            ->boolean('emergency_preparedness_reviewed')
            ->scalar('care_plan_goals')
            ->notEmptyString('care_plan_goals', 'Care plan goals are required on finalized assessments.', function (array $context): bool {
                return in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->allowEmptyString('care_plan_goals', null, function (array $context): bool {
                return !in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->scalar('clinical_summary')
            ->notEmptyString('clinical_summary', 'Clinical summary is required on finalized assessments.', function (array $context): bool {
                return in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->allowEmptyString('clinical_summary', null, function (array $context): bool {
                return !in_array((string)($context['data']['status'] ?? ''), ['final', 'locked'], true);
            })
            ->allowEmptyString('assessment_payload')
            ->allowEmptyString('answers');

        $validator
            ->add('medication_reconciliation_completed', 'requiredMedicationRecForFinalSoc', [
                'rule' => function ($value, array $context): bool {
                    $status = (string)($context['data']['status'] ?? '');
                    $assessmentType = strtolower((string)($context['data']['assessment_type'] ?? ''));
                    $requiresMedicationRec = in_array($assessmentType, ['soc', 'roc', 'recertification'], true);

                    return !in_array($status, ['final', 'locked'], true) || !$requiresMedicationRec || (bool)$value;
                },
                'message' => 'Medication reconciliation must be completed on finalized SOC, ROC, and recertification assessments.',
            ]);

        return $validator;
    }
}
