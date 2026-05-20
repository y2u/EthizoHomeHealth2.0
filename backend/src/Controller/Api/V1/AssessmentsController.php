<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\AssessmentVersionResolver;

class AssessmentsController extends ApiController
{
    public function index()
    {
        $query = $this->fetchTable('Assessments')->find()->contain(['Episodes']);
        $episodeId = $this->request->getQuery('episode_id');
        if ($episodeId !== null) {
            $query->where(['episode_id' => $episodeId]);
        }

        return $this->respond([
            'success' => true,
            'data' => $query->orderByDesc('completed_at')->all()->toList(),
        ]);
    }

    public function add()
    {
        $assessments = $this->fetchTable('Assessments');
        $assessment = $assessments->newEntity($this->normalizeAssessmentPayload($this->body()));
        if ($assessment->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $assessment->getErrors(),
            ], 422);
        }

        $assessments->saveOrFail($assessment);
        $this->upsertAssessmentQaTask($assessment);

        return $this->respond([
            'success' => true,
            'data' => $this->normalizeAssessmentEntity($assessment),
        ], 201);
    }

    public function update(int $id)
    {
        $assessments = $this->fetchTable('Assessments');
        $assessment = $assessments->get($id);
        $assessment = $assessments->patchEntity($assessment, $this->normalizeAssessmentPayload($this->body(), $assessment->toArray()));
        if ($assessment->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $assessment->getErrors(),
            ], 422);
        }

        $assessments->saveOrFail($assessment);
        $this->upsertAssessmentQaTask($assessment);

        return $this->respond([
            'success' => true,
            'data' => $this->normalizeAssessmentEntity($assessment),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function normalizeAssessmentPayload(array $data, array $existing = []): array
    {
        $normalized = $data;
        $normalized['completed_at'] = $data['completed_at'] ?? $existing['completed_at'] ?? date('Y-m-d H:i:s');
        $normalized['oasis_version'] = (new AssessmentVersionResolver())->resolve((string)$normalized['completed_at']);

        $answers = $data['answers'] ?? $existing['answers'] ?? [];
        if (is_string($answers)) {
            $decodedAnswers = json_decode($answers, true);
            $answers = is_array($decodedAnswers) ? $decodedAnswers : [];
        }
        $normalized['answers'] = json_encode($answers, JSON_THROW_ON_ERROR);

        $assessmentPayload = $data['assessment_payload'] ?? $existing['assessment_payload'] ?? [];
        if (is_string($assessmentPayload)) {
            $decodedPayload = json_decode($assessmentPayload, true);
            $assessmentPayload = is_array($decodedPayload) ? $decodedPayload : [];
        }
        $normalized['assessment_payload'] = json_encode($assessmentPayload, JSON_THROW_ON_ERROR);

        $normalized['medication_reconciliation_completed'] = (bool)($data['medication_reconciliation_completed'] ?? $existing['medication_reconciliation_completed'] ?? false);
        $normalized['homebound_status'] = trim((string)($data['homebound_status'] ?? $existing['homebound_status'] ?? ''));
        $normalized['homebound_narrative'] = trim((string)($data['homebound_narrative'] ?? $existing['homebound_narrative'] ?? ''));
        $normalized['fall_risk_level'] = trim((string)($data['fall_risk_level'] ?? $existing['fall_risk_level'] ?? ''));
        $normalized['hospitalization_risk'] = trim((string)($data['hospitalization_risk'] ?? $existing['hospitalization_risk'] ?? ''));
        $normalized['emergency_preparedness_reviewed'] = (bool)($data['emergency_preparedness_reviewed'] ?? $existing['emergency_preparedness_reviewed'] ?? false);
        $normalized['care_plan_goals'] = trim((string)($data['care_plan_goals'] ?? $existing['care_plan_goals'] ?? ''));
        $normalized['clinical_summary'] = trim((string)($data['clinical_summary'] ?? $existing['clinical_summary'] ?? ''));

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAssessmentEntity(object $assessment): array
    {
        $data = $assessment->toArray();
        $data['answers'] = $this->decodeJsonField($data['answers'] ?? null);
        $data['assessment_payload'] = $this->decodeJsonField($data['assessment_payload'] ?? null);

        return $data;
    }

    private function upsertAssessmentQaTask(object $assessment): void
    {
        $qaTasks = $this->fetchTable('QaTasks');
        $existingTask = $qaTasks->find()
            ->where([
                'assessment_id' => $assessment->get('id'),
                'task_type' => 'assessment_review',
                'status' => 'open',
            ])
            ->first();

        $payload = [
            'episode_id' => $assessment->get('episode_id'),
            'assessment_id' => $assessment->get('id'),
            'task_type' => 'assessment_review',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Review OASIS submission readiness',
            'details' => $this->buildAssessmentReviewDetails($assessment),
            'assigned_role' => 'QA',
            'due_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ];

        if ($existingTask !== null) {
            $existingTask = $qaTasks->patchEntity($existingTask, $payload);
            $qaTasks->saveOrFail($existingTask);

            return;
        }

        $qaTask = $qaTasks->newEntity($payload);
        $qaTasks->saveOrFail($qaTask);
    }

    private function buildAssessmentReviewDetails(object $assessment): string
    {
        $parts = [
            sprintf('Assessment %s requires QA review.', (string)$assessment->get('oasis_version')),
            sprintf('Homebound: %s.', (string)$assessment->get('homebound_status') !== '' ? (string)$assessment->get('homebound_status') : 'not documented'),
            sprintf('Medication reconciliation: %s.', (bool)$assessment->get('medication_reconciliation_completed') ? 'completed' : 'pending'),
            sprintf('Fall risk: %s.', (string)$assessment->get('fall_risk_level') !== '' ? (string)$assessment->get('fall_risk_level') : 'not documented'),
            sprintf('Hospitalization risk: %s.', (string)$assessment->get('hospitalization_risk') !== '' ? (string)$assessment->get('hospitalization_risk') : 'not documented'),
        ];

        return implode(' ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonField(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
