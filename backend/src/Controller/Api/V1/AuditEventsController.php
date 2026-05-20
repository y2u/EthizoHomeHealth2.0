<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

class AuditEventsController extends ApiController
{
    public function index()
    {
        $query = $this->fetchTable('AuditEvents')->find()
            ->orderByDesc('created');

        $action = trim((string)$this->request->getQuery('action', ''));
        $model = trim((string)$this->request->getQuery('model', ''));
        $actorEmail = trim((string)$this->request->getQuery('actor_email', ''));
        $limit = max(1, min((int)$this->request->getQuery('limit', 75), 200));

        if ($action !== '') {
            $query->where(['action' => $action]);
        }
        if ($model !== '') {
            $query->where(['model' => $model]);
        }
        if ($actorEmail !== '') {
            $query->where(['actor_email LIKE' => '%' . $actorEmail . '%']);
        }

        $events = array_map(function ($event): array {
            $data = $event->toArray();
            $details = $data['details'] ?? null;
            if (is_string($details) && trim($details) !== '') {
                $decoded = json_decode($details, true);
                $data['details'] = is_array($decoded) ? $decoded : $details;
            }

            return $data;
        }, $query->limit($limit)->all()->toList());

        return $this->respond([
            'success' => true,
            'data' => $events,
        ]);
    }
}
