<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Table;

class AuditLogger
{
    public function __construct(private readonly Table $auditEvents)
    {
    }

    /**
     * @param array<string, mixed> $identity
     * @param array<string, mixed> $details
     */
    public function log(array $identity, string $action, string $model, int $modelId, array $details = []): void
    {
        $event = $this->auditEvents->newEntity([
            'actor_email' => (string)($identity['email'] ?? 'system'),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'created' => date('Y-m-d H:i:s'),
        ]);

        $this->auditEvents->saveOrFail($event);
    }
}
