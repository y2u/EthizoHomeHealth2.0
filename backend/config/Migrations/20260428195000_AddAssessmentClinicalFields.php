<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAssessmentClinicalFields extends BaseMigration
{
    public function change(): void
    {
        $assessments = $this->table('assessments');

        if (!$assessments->hasColumn('medication_reconciliation_completed')) {
            $assessments->addColumn('medication_reconciliation_completed', 'boolean', ['default' => false, 'after' => 'comorbidity_level']);
        }
        if (!$assessments->hasColumn('homebound_status')) {
            $assessments->addColumn('homebound_status', 'string', ['limit' => 60, 'null' => true, 'after' => 'medication_reconciliation_completed']);
        }
        if (!$assessments->hasColumn('homebound_narrative')) {
            $assessments->addColumn('homebound_narrative', 'text', ['null' => true, 'after' => 'homebound_status']);
        }
        if (!$assessments->hasColumn('fall_risk_level')) {
            $assessments->addColumn('fall_risk_level', 'string', ['limit' => 40, 'null' => true, 'after' => 'homebound_narrative']);
        }
        if (!$assessments->hasColumn('hospitalization_risk')) {
            $assessments->addColumn('hospitalization_risk', 'string', ['limit' => 40, 'null' => true, 'after' => 'fall_risk_level']);
        }
        if (!$assessments->hasColumn('emergency_preparedness_reviewed')) {
            $assessments->addColumn('emergency_preparedness_reviewed', 'boolean', ['default' => false, 'after' => 'hospitalization_risk']);
        }
        if (!$assessments->hasColumn('care_plan_goals')) {
            $assessments->addColumn('care_plan_goals', 'text', ['null' => true, 'after' => 'emergency_preparedness_reviewed']);
        }
        if (!$assessments->hasColumn('clinical_summary')) {
            $assessments->addColumn('clinical_summary', 'text', ['null' => true, 'after' => 'care_plan_goals']);
        }
        if (!$assessments->hasColumn('assessment_payload')) {
            $assessments->addColumn('assessment_payload', 'text', ['null' => true, 'after' => 'clinical_summary']);
        }

        $assessments->update();
    }
}
