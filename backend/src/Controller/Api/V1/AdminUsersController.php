<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\AuditLogger;

class AdminUsersController extends ApiController
{
    public function index()
    {
        $users = $this->fetchTable('Users')->find()
            ->orderByAsc('full_name')
            ->all()
            ->map(fn ($user) => $this->serializeUser($user->toArray()))
            ->toList();

        return $this->respond([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function add()
    {
        $body = $this->body();
        $users = $this->fetchTable('Users');
        $password = trim((string)($body['password'] ?? ''));

        $user = $users->newEntity([
            'full_name' => trim((string)($body['full_name'] ?? '')),
            'email' => trim((string)($body['email'] ?? '')),
            'password_hash' => password_hash($password !== '' ? $password : 'demo1234', PASSWORD_DEFAULT),
            'role' => trim((string)($body['role'] ?? 'Intake')),
            'mobile' => trim((string)($body['mobile'] ?? '')) ?: null,
            'status' => trim((string)($body['status'] ?? 'active')) ?: 'active',
            'mfa_enabled' => (bool)($body['mfa_enabled'] ?? false),
        ]);

        if ($user->hasErrors()) {
            return $this->respond([
                'success' => false,
                'message' => 'User validation failed.',
                'errors' => $user->getErrors(),
            ], 422);
        }

        $users->saveOrFail($user);
        (new AuditLogger($this->fetchTable('AuditEvents')))->log(
            $this->identity(),
            'admin_user_added',
            'User',
            (int)$user->get('id'),
            [
                'email' => $user->get('email'),
                'role' => $user->get('role'),
                'status' => $user->get('status'),
            ],
        );

        return $this->respond([
            'success' => true,
            'data' => $this->serializeUser($user->toArray()),
        ], 201);
    }

    public function update(int $id)
    {
        $body = $this->body();
        $users = $this->fetchTable('Users');
        $user = $users->get($id);

        $patch = [
            'full_name' => trim((string)($body['full_name'] ?? $user->get('full_name'))),
            'email' => trim((string)($body['email'] ?? $user->get('email'))),
            'role' => trim((string)($body['role'] ?? $user->get('role'))),
            'mobile' => trim((string)($body['mobile'] ?? $user->get('mobile'))) ?: null,
            'status' => trim((string)($body['status'] ?? $user->get('status'))) ?: 'active',
            'mfa_enabled' => array_key_exists('mfa_enabled', $body) ? (bool)$body['mfa_enabled'] : (bool)$user->get('mfa_enabled'),
        ];
        $password = trim((string)($body['password'] ?? ''));
        if ($password !== '') {
            $patch['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $user = $users->patchEntity($user, $patch);
        if ($user->hasErrors()) {
            return $this->respond([
                'success' => false,
                'message' => 'User validation failed.',
                'errors' => $user->getErrors(),
            ], 422);
        }

        $users->saveOrFail($user);
        (new AuditLogger($this->fetchTable('AuditEvents')))->log(
            $this->identity(),
            'admin_user_updated',
            'User',
            $id,
            [
                'email' => $user->get('email'),
                'role' => $user->get('role'),
                'status' => $user->get('status'),
                'mfa_enabled' => (bool)$user->get('mfa_enabled'),
            ],
        );

        return $this->respond([
            'success' => true,
            'data' => $this->serializeUser($user->toArray()),
        ]);
    }

    public function sessionActivity()
    {
        $users = $this->fetchTable('Users')->find()
            ->orderByAsc('full_name')
            ->all()
            ->toList();
        $auditEvents = $this->fetchTable('AuditEvents')->find()
            ->orderByDesc('created')
            ->all()
            ->toList();

        $eventsByActor = [];
        foreach ($auditEvents as $event) {
            $actorEmail = (string)$event->get('actor_email');
            if ($actorEmail !== '' && !isset($eventsByActor[$actorEmail])) {
                $eventsByActor[$actorEmail] = $event;
            }
        }

        $sessions = array_map(function ($user) use ($eventsByActor): array {
            $email = (string)$user->get('email');
            $lastLoginAt = $user->get('last_login_at');
            $recentEvent = $eventsByActor[$email] ?? null;
            $activityState = 'never_logged_in';
            if ($lastLoginAt !== null) {
                $activityState = strtotime((string)$lastLoginAt) >= strtotime('-12 hours') ? 'active_window' : 'stale_window';
            }

            return [
                'user_id' => (int)$user->get('id'),
                'full_name' => (string)$user->get('full_name'),
                'email' => $email,
                'role' => (string)$user->get('role'),
                'status' => (string)$user->get('status'),
                'mfa_enabled' => (bool)$user->get('mfa_enabled'),
                'last_login_at' => $lastLoginAt,
                'activity_state' => $activityState,
                'recent_action' => $recentEvent?->get('action'),
                'recent_model' => $recentEvent?->get('model'),
                'recent_at' => $recentEvent?->get('created'),
            ];
        }, $users);

        usort($sessions, fn (array $left, array $right) => strcmp((string)$right['last_login_at'], (string)$left['last_login_at']));

        return $this->respond([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function serializeUser(array $user): array
    {
        unset($user['password_hash']);

        return $user;
    }
}
