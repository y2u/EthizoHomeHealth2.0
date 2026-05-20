<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;

class QaTaskEscalationService
{
    /**
     * @param array<int, object> $tasks
     * @return array<int, array<string, mixed>>
     */
    public function enrichTasks(array $tasks): array
    {
        $normalized = array_map(fn (object $task): array => $this->enrichTask($task), $tasks);
        usort($normalized, function (array $left, array $right): int {
            $statusRank = [
                'overdue_assigned' => 4,
                'overdue_unassigned' => 3,
                'due_today' => 2,
                'upcoming' => 1,
                'undated' => 0,
            ];
            $priorityRank = [
                'high' => 3,
                'medium' => 2,
                'low' => 1,
            ];

            $leftStatus = $statusRank[$left['escalation_status'] ?? 'undated'] ?? 0;
            $rightStatus = $statusRank[$right['escalation_status'] ?? 'undated'] ?? 0;
            if ($leftStatus !== $rightStatus) {
                return $rightStatus <=> $leftStatus;
            }

            $leftPriority = $priorityRank[strtolower((string)($left['priority'] ?? 'medium'))] ?? 0;
            $rightPriority = $priorityRank[strtolower((string)($right['priority'] ?? 'medium'))] ?? 0;
            if ($leftPriority !== $rightPriority) {
                return $rightPriority <=> $leftPriority;
            }

            return strcmp((string)($left['due_at'] ?? ''), (string)($right['due_at'] ?? ''));
        });

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichTask(object $task): array
    {
        $data = method_exists($task, 'toArray') ? $task->toArray() : (array)$task;
        $basePriority = strtolower((string)($data['priority'] ?? 'medium'));
        $dueAt = $this->normalizeDateTimeString($data['due_at'] ?? null);
        $now = new DateTime();
        $today = $now->format('Y-m-d');
        $effectivePriority = $basePriority;
        $escalationStatus = 'undated';
        $escalationReason = null;
        $isOverdue = false;

        if ($dueAt !== null) {
            $dueDateTime = new DateTime($dueAt);
            $dueDate = $dueDateTime->format('Y-m-d');

            if ($dueDateTime < $now) {
                $isOverdue = true;
                $effectivePriority = 'high';
                $isAssigned = trim((string)($data['assigned_user_name'] ?? '')) !== '' || trim((string)($data['assigned_role'] ?? '')) !== '';
                $escalationStatus = $isAssigned ? 'overdue_assigned' : 'overdue_unassigned';
                $escalationReason = $isAssigned
                    ? sprintf('%s owns overdue follow-up that should move immediately.', $this->formatAssignee($data))
                    : 'This overdue task needs an owner immediately.';
            } elseif ($dueDate === $today) {
                $escalationStatus = 'due_today';
                $effectivePriority = $basePriority === 'low' ? 'medium' : $basePriority;
                $escalationReason = trim((string)($data['assigned_user_name'] ?? '')) !== ''
                    ? sprintf('%s has work due today that should stay near the top of the queue.', $this->formatAssignee($data))
                    : 'This task is due today and should stay visible.';
            } else {
                $escalationStatus = 'upcoming';
            }
        }

        $data['base_priority'] = $basePriority;
        $data['priority'] = $effectivePriority;
        $data['due_at'] = $dueAt;
        $data['assignment_history'] = $this->decodeHistory($data['assignment_history'] ?? null);
        $data['escalation_note'] = $this->normalizeString($data['escalation_note'] ?? null);
        $data['last_escalated_at'] = $this->normalizeDateTimeString($data['last_escalated_at'] ?? null);
        $data['escalation_status'] = $escalationStatus;
        $data['escalation_reason'] = $escalationReason;
        $data['is_overdue'] = $isOverdue;

        return $data;
    }

    private function formatAssignee(array $task): string
    {
        $name = trim((string)($task['assigned_user_name'] ?? ''));
        $role = trim((string)($task['assigned_role'] ?? ''));

        if ($name !== '' && $role !== '') {
            return sprintf('%s (%s)', $name, $role);
        }
        if ($name !== '') {
            return $name;
        }
        if ($role !== '') {
            return $role;
        }

        return 'The assigned team';
    }

    private function normalizeDateTimeString(mixed $value): ?string
    {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return str_replace('T', ' ', substr($value, 0, 19));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeHistory(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_array'));
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
