<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use RuntimeException;
use App\Service\ClaimHoldService;

class VisitsController extends ApiController
{
    public function index()
    {
        $query = $this->fetchTable('Visits')->find()->contain(['Episodes']);
        $episodeId = $this->request->getQuery('episode_id');
        if ($episodeId !== null) {
            $query->where(['Visits.episode_id' => $episodeId]);
        }

        return $this->respond([
            'success' => true,
            'data' => $query->orderByAsc('scheduled_start')->all()->toList(),
        ]);
    }

    public function add()
    {
        $visits = $this->fetchTable('Visits');
        $data = $this->body();
        $data['documentation_status'] = $data['documentation_status'] ?? 'pending';
        $visit = $visits->newEntity($data);
        if ($visit->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $visit->getErrors(),
            ], 422);
        }

        $visits->saveOrFail($visit);

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ], 201);
    }

    public function checkIn(int $id)
    {
        return $this->logVisitEvent($id, 'check_in', 'in_progress', 'checked_in');
    }

    public function checkOut(int $id)
    {
        return $this->logVisitEvent($id, 'check_out', 'completed', 'completed');
    }

    public function document(int $id)
    {
        $visits = $this->fetchTable('Visits');
        $visit = $visits->get($id, contain: ['Episodes']);
        if ((string)$visit->get('documentation_status') === 'locked') {
            return $this->respond([
                'success' => false,
                'message' => 'Locked documentation cannot be edited.',
            ], 422);
        }

        $data = $this->body();
        $documentationPayload = $this->normalizeDocumentationPayload($data);
        $submitForQa = (bool)($data['submit_for_qa'] ?? false);
        $validationError = $this->validateDocumentationPayload($visit, $documentationPayload, $submitForQa);
        if ($validationError !== null) {
            return $this->respond([
                'success' => false,
                'message' => $validationError,
            ], 422);
        }
        $summary = $this->buildDocumentationSummary($documentationPayload);
        $nextDocumentationStatus = $submitForQa
            ? 'qa_review'
            : ((string)$visit->get('status') === 'completed' ? 'completed' : (string)$visit->get('documentation_status'));

        $visit = $visits->patchEntity($visit, [
            'documentation_payload' => json_encode($documentationPayload, JSON_THROW_ON_ERROR),
            'documentation_summary' => $summary !== '' ? $summary : $visit->get('documentation_summary'),
            'follow_up_plan' => $documentationPayload['follow_up_plan'] !== '' ? $documentationPayload['follow_up_plan'] : $visit->get('follow_up_plan'),
            'documentation_status' => $nextDocumentationStatus,
        ]);
        $visits->saveOrFail($visit);

        if ($submitForQa) {
            $this->upsertDocumentationQaTask($visit, $documentationPayload);
            (new ClaimHoldService())->syncEpisodeClaimHolds((int)$visit->get('episode_id'));
        }

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ]);
    }

    public function lockDocumentation(int $id)
    {
        $visits = $this->fetchTable('Visits');
        $visit = $visits->get($id);
        if (!in_array((string)$visit->get('status'), ['completed', 'locked'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Visit must be completed before documentation can be locked.',
            ], 422);
        }

        $qaReviewNotes = trim((string)($this->body()['qa_review_notes'] ?? ''));
        $visit = $visits->patchEntity($visit, [
            'status' => 'locked',
            'documentation_status' => 'locked',
            'qa_review_notes' => $qaReviewNotes !== '' ? $qaReviewNotes : $visit->get('qa_review_notes'),
        ]);
        $visits->saveOrFail($visit);

        $qaTasks = $this->fetchTable('QaTasks')->find()
            ->where([
                'visit_id' => $id,
                'task_type' => 'visit_documentation_review',
                'status' => 'open',
            ])
            ->all();

        foreach ($qaTasks as $task) {
            $task = $this->fetchTable('QaTasks')->patchEntity($task, ['status' => 'resolved']);
            $this->fetchTable('QaTasks')->saveOrFail($task);
        }

        (new ClaimHoldService())->syncEpisodeClaimHolds((int)$visit->get('episode_id'));

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ]);
    }

    public function markMissed(int $id)
    {
        $visits = $this->fetchTable('Visits');
        $visit = $visits->get($id, contain: ['Episodes']);
        if (in_array((string)$visit->get('status'), ['completed', 'locked'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Completed visits cannot be marked as missed.',
            ], 422);
        }

        $reason = trim((string)($this->body()['reason'] ?? 'Visit missed and requires follow-up review.'));
        $followUpPlan = trim((string)($this->body()['follow_up_plan'] ?? 'Care team to confirm patient status and determine whether rescheduling is required.'));
        $visit = $visits->patchEntity($visit, [
            'status' => 'missed',
            'sync_status' => 'missed',
            'documentation_status' => 'exception_review',
            'missed_reason' => $reason,
            'follow_up_plan' => $followUpPlan,
            'documentation_summary' => $this->appendNote((string)$visit->get('documentation_summary'), 'Missed visit: ' . $reason . ' Follow-up plan: ' . $followUpPlan),
        ]);
        $visits->saveOrFail($visit);

        $this->createQaTask(
            (int)$visit->get('episode_id'),
            (int)$visit->get('id'),
            'missed_visit',
            'high',
            'Review missed visit and follow-up actions',
            $reason . ' Follow-up plan: ' . $followUpPlan,
            date('Y-m-d 09:00:00'),
        );
        $this->applyClaimHold((int)$visit->get('episode_id'), 'Missed visit requires QA and billing review before submission.');

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ]);
    }

    public function reassign(int $id)
    {
        $visits = $this->fetchTable('Visits');
        $visit = $visits->get($id, contain: ['Episodes']);
        if (in_array((string)$visit->get('status'), ['completed', 'locked'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Completed visits cannot be reassigned.',
            ], 422);
        }

        $data = $this->body();
        $newClinician = trim((string)($data['clinician_name'] ?? ''));
        if ($newClinician === '') {
            return $this->respond([
                'success' => false,
                'message' => 'New clinician is required for reassignment.',
            ], 422);
        }

        $oldClinician = (string)$visit->get('clinician_name');
        $reason = trim((string)($data['reason'] ?? 'Visit staffing assignment updated.'));
        $followUpPlan = trim((string)($data['follow_up_plan'] ?? 'New clinician to confirm visit timing and documentation readiness.'));

        $visit = $visits->patchEntity($visit, [
            'clinician_name' => $newClinician,
            'reassigned_from_clinician' => $oldClinician,
            'sync_status' => 'reassigned',
            'documentation_status' => 'qa_review',
            'follow_up_plan' => $followUpPlan,
            'documentation_summary' => $this->appendNote(
                (string)$visit->get('documentation_summary'),
                sprintf('Reassigned from %s to %s. %s Follow-up plan: %s', $oldClinician, $newClinician, $reason, $followUpPlan),
            ),
        ]);
        $visits->saveOrFail($visit);

        $this->createQaTask(
            (int)$visit->get('episode_id'),
            (int)$visit->get('id'),
            'visit_reassignment',
            'medium',
            'Review visit reassignment and staffing handoff',
            sprintf('%s reassigned to %s. %s Follow-up plan: %s', $oldClinician, $newClinician, $reason, $followUpPlan),
            date('Y-m-d 09:00:00'),
        );
        $this->applyClaimHold((int)$visit->get('episode_id'), 'Visit reassignment requires QA and billing review before submission.');

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ]);
    }

    public function reschedule(int $id)
    {
        $visits = $this->fetchTable('Visits');
        $visit = $visits->get($id, contain: ['Episodes']);
        if (in_array((string)$visit->get('status'), ['completed', 'locked'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Completed visits cannot be rescheduled.',
            ], 422);
        }

        $data = $this->body();
        $newStart = (string)($data['scheduled_start'] ?? '');
        $newEnd = (string)($data['scheduled_end'] ?? '');
        if ($newStart === '' || $newEnd === '') {
            return $this->respond([
                'success' => false,
                'message' => 'New scheduled start and end times are required.',
            ], 422);
        }

        $originalStart = (string)$visit->get('scheduled_start');
        $originalVisitType = (string)$visit->get('visit_type');
        $originalDiscipline = (string)$visit->get('discipline');
        $reason = trim((string)($data['reason'] ?? 'Schedule updated after activation.'));
        $episodeStatus = (string)$visit->get('episode')->get('episode_status');
        $changedTiming = substr($originalStart, 0, 10) !== substr($newStart, 0, 10);
        $changedPlan = $originalVisitType !== (string)($data['visit_type'] ?? $originalVisitType)
            || $originalDiscipline !== (string)($data['discipline'] ?? $originalDiscipline);
        $needsReview = $episodeStatus === 'active' || $changedTiming || $changedPlan;

        $visit = $visits->patchEntity($visit, [
            'scheduled_start' => $newStart,
            'scheduled_end' => $newEnd,
            'visit_type' => $data['visit_type'] ?? $originalVisitType,
            'discipline' => $data['discipline'] ?? $originalDiscipline,
            'sync_status' => 'rescheduled',
            'status' => 'scheduled',
            'documentation_status' => $needsReview ? 'qa_review' : 'pending',
            'follow_up_plan' => trim((string)($data['follow_up_plan'] ?? $visit->get('follow_up_plan'))),
            'documentation_summary' => $this->appendNote(
                (string)$visit->get('documentation_summary'),
                sprintf('Rescheduled from %s to %s. %s', $originalStart, $newStart, $reason),
            ),
        ]);

        if ($visit->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $visit->getErrors(),
            ], 422);
        }

        $visits->saveOrFail($visit);

        if ($needsReview) {
            $details = sprintf(
                'Schedule changed from %s %s on %s to %s %s on %s. %s',
                $originalDiscipline,
                $originalVisitType,
                $originalStart,
                (string)$visit->get('discipline'),
                (string)$visit->get('visit_type'),
                $newStart,
                $reason,
            );
            $this->createQaTask(
                (int)$visit->get('episode_id'),
                (int)$visit->get('id'),
                'frequency_change',
                'medium',
                'Review frequency or schedule change after activation',
                $details,
                date('Y-m-d 09:00:00'),
            );
            $this->applyClaimHold((int)$visit->get('episode_id'), 'Schedule change requires QA and billing review before submission.');
        }

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ]);
    }

    private function logVisitEvent(int $id, string $eventType, string $visitStatus, string $syncStatus)
    {
        $visits = $this->fetchTable('Visits');
        $visit = $visits->get($id);
        $data = $this->body();
        $eventTime = $data['event_time'] ?? date('Y-m-d H:i:s');

        $event = $this->fetchTable('CheckInOutEvents')->newEntity([
            'visit_id' => $id,
            'event_type' => $eventType,
            'event_time' => $eventTime,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy_meters' => $data['accuracy_meters'] ?? null,
            'device_metadata' => json_encode($data['device_metadata'] ?? [], JSON_THROW_ON_ERROR),
            'source' => $data['source'] ?? 'mobile_web',
        ]);
        $this->fetchTable('CheckInOutEvents')->saveOrFail($event);

        $patch = [
            'status' => $visitStatus,
            'sync_status' => $syncStatus,
        ];
        if ($eventType === 'check_in') {
            $patch['actual_start'] = $eventTime;
            $patch['documentation_status'] = 'in_progress';
        } else {
            $patch['actual_end'] = $eventTime;
            $patch['documentation_summary'] = $data['documentation_summary'] ?? $visit->get('documentation_summary');
            $patch['documentation_status'] = 'completed';
        }

        $visit = $visits->patchEntity($visit, $patch);
        $visits->saveOrFail($visit);

        if ((bool)$visit->get('requires_evv') && $eventType === 'check_out') {
            $evvRecord = $this->fetchTable('EvvRecords')->newEntity([
                'visit_id' => $visit->get('id'),
                'state_code' => 'GA',
                'vendor_name' => 'Georgia EVV Sandbox',
                'status' => 'pending_submission',
                'payload' => json_encode([
                    'visit_id' => $visit->get('id'),
                    'check_in' => $visit->get('actual_start'),
                    'check_out' => $visit->get('actual_end'),
                    'lat' => $data['latitude'] ?? null,
                    'lng' => $data['longitude'] ?? null,
                ], JSON_THROW_ON_ERROR),
            ]);
            $this->fetchTable('EvvRecords')->saveOrFail($evvRecord);
        }

        return $this->respond([
            'success' => true,
            'data' => $visit,
        ]);
    }

    private function createQaTask(
        int $episodeId,
        int $visitId,
        string $taskType,
        string $priority,
        string $title,
        string $details,
        string $dueAt,
    ): void {
        $qaTask = $this->fetchTable('QaTasks')->newEntity([
            'episode_id' => $episodeId,
            'visit_id' => $visitId,
            'task_type' => $taskType,
            'priority' => $priority,
            'status' => 'open',
            'title' => $title,
            'details' => $details,
            'assigned_role' => 'QA',
            'due_at' => $dueAt,
        ]);
        $this->fetchTable('QaTasks')->saveOrFail($qaTask);
    }

    /**
     * @param array<string, mixed> $documentationPayload
     */
    private function upsertDocumentationQaTask(object $visit, array $documentationPayload): void
    {
        $qaTasks = $this->fetchTable('QaTasks');
        $details = trim(implode(' ', array_filter([
            $documentationPayload['visit_focus'] !== '' ? 'Visit focus: ' . $documentationPayload['visit_focus'] . '.' : null,
            $documentationPayload['visit_narrative'] !== '' ? 'Narrative: ' . $documentationPayload['visit_narrative'] . '.' : null,
            $documentationPayload['interventions'] !== '' ? 'Interventions: ' . $documentationPayload['interventions'] . '.' : null,
            $documentationPayload['patient_response'] !== '' ? 'Patient response: ' . $documentationPayload['patient_response'] . '.' : null,
            $documentationPayload['vitals'] !== '' ? 'Vitals: ' . $documentationPayload['vitals'] . '.' : null,
            $documentationPayload['teaching_topics'] !== '' ? 'Teaching: ' . $documentationPayload['teaching_topics'] . '.' : null,
            $documentationPayload['medication_review'] !== '' ? 'Medication review: ' . $documentationPayload['medication_review'] . '.' : null,
            $documentationPayload['wound_care'] !== '' ? 'Wound care: ' . $documentationPayload['wound_care'] . '.' : null,
            $documentationPayload['mobility_status'] !== '' ? 'Mobility: ' . $documentationPayload['mobility_status'] . '.' : null,
            $documentationPayload['adl_support'] !== '' ? 'ADL support: ' . $documentationPayload['adl_support'] . '.' : null,
            $documentationPayload['psychosocial_notes'] !== '' ? 'Psychosocial: ' . $documentationPayload['psychosocial_notes'] . '.' : null,
            $documentationPayload['abnormal_findings'] !== '' ? 'Abnormal findings: ' . $documentationPayload['abnormal_findings'] . '.' : null,
            $documentationPayload['physician_contact_needed'] ? 'Physician contact is needed.' : null,
            $documentationPayload['follow_up_plan'] !== '' ? 'Follow-up plan: ' . $documentationPayload['follow_up_plan'] . '.' : null,
            $documentationPayload['next_visit_focus'] !== '' ? 'Next visit focus: ' . $documentationPayload['next_visit_focus'] . '.' : null,
        ])));
        $existingTask = $qaTasks->find()
            ->where([
                'visit_id' => $visit->get('id'),
                'task_type' => 'visit_documentation_review',
                'status' => 'open',
            ])
            ->first();

        if ($existingTask !== null) {
            $existingTask = $qaTasks->patchEntity($existingTask, [
                'details' => $details,
                'due_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            ]);
            $qaTasks->saveOrFail($existingTask);

            return;
        }

        $qaTask = $qaTasks->newEntity([
            'episode_id' => $visit->get('episode_id'),
            'visit_id' => $visit->get('id'),
            'task_type' => 'visit_documentation_review',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Review and lock visit documentation',
            'details' => $details,
            'assigned_role' => 'QA',
            'due_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);
        $qaTasks->saveOrFail($qaTask);
    }

    private function applyClaimHold(int $episodeId, string $reason): void
    {
        $claims = $this->fetchTable('Claims')->find()
            ->where(['episode_id' => $episodeId, 'status IN' => ['draft', 'ready']])
            ->all();

        foreach ($claims as $claim) {
            $currentReason = trim((string)$claim->get('hold_reason'));
            $nextReason = $currentReason === '' || str_contains($currentReason, $reason) ? $reason : $currentReason . ' | ' . $reason;
            $claim = $this->fetchTable('Claims')->patchEntity($claim, [
                'hold_reason' => $nextReason,
            ]);
            $this->fetchTable('Claims')->saveOrFail($claim);
        }

        (new ClaimHoldService())->syncEpisodeClaimHolds($episodeId);
    }

    private function appendNote(string $existing, string $note): string
    {
        return trim($existing === '' ? $note : $existing . ' ' . $note);
    }

    /**
     * @param array<string, mixed> $documentationPayload
     */
    private function buildDocumentationSummary(array $documentationPayload): string
    {
        return trim(implode(' ', array_filter([
            $documentationPayload['visit_focus'] !== '' ? 'Focus: ' . $documentationPayload['visit_focus'] . '.' : null,
            $documentationPayload['visit_narrative'] !== '' ? 'Narrative: ' . $documentationPayload['visit_narrative'] . '.' : null,
            $documentationPayload['interventions'] !== '' ? 'Interventions: ' . $documentationPayload['interventions'] . '.' : null,
            $documentationPayload['patient_response'] !== '' ? 'Patient response: ' . $documentationPayload['patient_response'] . '.' : null,
            $documentationPayload['vitals'] !== '' ? 'Vitals: ' . $documentationPayload['vitals'] . '.' : null,
            $documentationPayload['pain_level'] !== '' ? 'Pain: ' . $documentationPayload['pain_level'] . '.' : null,
            $documentationPayload['teaching_topics'] !== '' ? 'Teaching: ' . $documentationPayload['teaching_topics'] . '.' : null,
            $documentationPayload['medication_review'] !== '' ? 'Medication review: ' . $documentationPayload['medication_review'] . '.' : null,
            $documentationPayload['wound_care'] !== '' ? 'Wound care: ' . $documentationPayload['wound_care'] . '.' : null,
            $documentationPayload['mobility_status'] !== '' ? 'Mobility: ' . $documentationPayload['mobility_status'] . '.' : null,
            $documentationPayload['adl_support'] !== '' ? 'ADL support: ' . $documentationPayload['adl_support'] . '.' : null,
            $documentationPayload['psychosocial_notes'] !== '' ? 'Psychosocial: ' . $documentationPayload['psychosocial_notes'] . '.' : null,
            $documentationPayload['abnormal_findings'] !== '' ? 'Abnormal findings: ' . $documentationPayload['abnormal_findings'] . '.' : null,
            $documentationPayload['physician_contact_needed'] ? 'Physician contact needed.' : null,
            $documentationPayload['follow_up_plan'] !== '' ? 'Follow-up plan: ' . $documentationPayload['follow_up_plan'] . '.' : null,
            $documentationPayload['next_visit_focus'] !== '' ? 'Next visit: ' . $documentationPayload['next_visit_focus'] . '.' : null,
        ])));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeDocumentationPayload(array $data): array
    {
        return [
            'visit_focus' => trim((string)($data['visit_focus'] ?? '')),
            'visit_narrative' => trim((string)($data['visit_narrative'] ?? '')),
            'interventions' => trim((string)($data['interventions'] ?? '')),
            'patient_response' => trim((string)($data['patient_response'] ?? '')),
            'vitals' => trim((string)($data['vitals'] ?? '')),
            'pain_level' => trim((string)($data['pain_level'] ?? '')),
            'teaching_topics' => trim((string)($data['teaching_topics'] ?? '')),
            'medication_review' => trim((string)($data['medication_review'] ?? '')),
            'wound_care' => trim((string)($data['wound_care'] ?? '')),
            'mobility_status' => trim((string)($data['mobility_status'] ?? '')),
            'adl_support' => trim((string)($data['adl_support'] ?? '')),
            'psychosocial_notes' => trim((string)($data['psychosocial_notes'] ?? '')),
            'abnormal_findings' => trim((string)($data['abnormal_findings'] ?? '')),
            'physician_contact_needed' => (bool)($data['physician_contact_needed'] ?? false),
            'follow_up_plan' => trim((string)($data['follow_up_plan'] ?? '')),
            'next_visit_focus' => trim((string)($data['next_visit_focus'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $documentationPayload
     */
    private function validateDocumentationPayload(object $visit, array $documentationPayload, bool $submitForQa): ?string
    {
        if (!$submitForQa) {
            return null;
        }

        $requiredForAll = [
            'visit_focus' => 'Visit focus',
            'visit_narrative' => 'Visit narrative',
            'interventions' => 'Interventions',
            'patient_response' => 'Patient response',
            'follow_up_plan' => 'Follow-up plan',
        ];
        foreach ($requiredForAll as $field => $label) {
            if (trim((string)$documentationPayload[$field]) === '') {
                return $label . ' is required before documentation can be submitted to QA.';
            }
        }

        $discipline = strtoupper(trim((string)$visit->get('discipline')));
        $requirements = match ($discipline) {
            'SN' => [
                'vitals' => 'Vitals',
                'medication_review' => 'Medication review',
                'teaching_topics' => 'Teaching topics',
            ],
            'PT', 'OT' => [
                'mobility_status' => 'Mobility status',
                'teaching_topics' => 'Teaching topics',
            ],
            'ST' => [
                'teaching_topics' => 'Teaching topics',
            ],
            'HHA' => [
                'adl_support' => 'ADL support',
            ],
            'MSW' => [
                'psychosocial_notes' => 'Psychosocial notes',
            ],
            default => [],
        };

        foreach ($requirements as $field => $label) {
            if (trim((string)$documentationPayload[$field]) === '') {
                return $label . ' is required before ' . $discipline . ' documentation can be submitted to QA.';
            }
        }

        return null;
    }
}
