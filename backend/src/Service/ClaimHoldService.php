<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ClaimHoldService
{
    public const GENERIC_QA_HOLD = 'Unresolved QA tasks must be completed before submission.';
    public const MISSED_VISIT_HOLD = 'Missed visit requires QA and billing review before submission.';
    public const FREQUENCY_CHANGE_HOLD = 'Schedule change requires QA and billing review before submission.';
    public const VISIT_REASSIGNMENT_HOLD = 'Visit reassignment requires QA and billing review before submission.';
    public const VISIT_DOCUMENTATION_HOLD = 'Visit documentation requires QA lock before submission.';

    private Table $claims;
    private Table $qaTasks;

    public function __construct()
    {
        $locator = TableRegistry::getTableLocator();
        $this->claims = $locator->get('Claims');
        $this->qaTasks = $locator->get('QaTasks');
    }

    public function syncEpisodeClaimHolds(int $episodeId): void
    {
        $openTasks = $this->qaTasks->find()
            ->where(['episode_id' => $episodeId, 'status' => 'open'])
            ->all()
            ->toList();

        $managedReasons = $this->managedReasonsForTasks($openTasks);
        $claims = $this->claims->find()
            ->where(['episode_id' => $episodeId, 'status IN' => ['draft', 'ready']])
            ->all();

        foreach ($claims as $claim) {
            $currentReasons = $this->splitReasons((string)$claim->get('hold_reason'));
            $unmanagedReasons = array_values(array_filter(
                $currentReasons,
                fn (string $reason) => !$this->isManagedReason($reason),
            ));
            $nextReasons = array_values(array_unique(array_merge($unmanagedReasons, $managedReasons)));
            $claim = $this->claims->patchEntity($claim, [
                'hold_reason' => $nextReasons === [] ? null : implode(' | ', $nextReasons),
            ]);
            $this->claims->saveOrFail($claim);
        }
    }

    /**
     * @param iterable<object> $tasks
     * @return array<int, string>
     */
    private function managedReasonsForTasks(iterable $tasks): array
    {
        $reasons = [];
        $hasGenericQaBlocker = false;

        foreach ($tasks as $task) {
            $taskType = (string)$task->get('task_type');
            if ($taskType === 'missed_visit') {
                $reasons[] = self::MISSED_VISIT_HOLD;
                continue;
            }
            if ($taskType === 'frequency_change') {
                $reasons[] = self::FREQUENCY_CHANGE_HOLD;
                continue;
            }
            if ($taskType === 'visit_reassignment') {
                $reasons[] = self::VISIT_REASSIGNMENT_HOLD;
                continue;
            }
            if ($taskType === 'visit_documentation_review') {
                $reasons[] = self::VISIT_DOCUMENTATION_HOLD;
                continue;
            }

            $hasGenericQaBlocker = true;
        }

        if ($hasGenericQaBlocker) {
            $reasons[] = self::GENERIC_QA_HOLD;
        }

        return array_values(array_unique($reasons));
    }

    private function isManagedReason(string $reason): bool
    {
        return in_array($reason, [
            self::GENERIC_QA_HOLD,
            self::MISSED_VISIT_HOLD,
            self::FREQUENCY_CHANGE_HOLD,
            self::VISIT_REASSIGNMENT_HOLD,
            self::VISIT_DOCUMENTATION_HOLD,
        ], true);
    }

    /**
     * @return array<int, string>
     */
    private function splitReasons(string $reason): array
    {
        if (trim($reason) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode('|', $reason))));
    }
}
