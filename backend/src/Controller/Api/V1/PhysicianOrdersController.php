<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\HomeHealthWorkflowService;
use InvalidArgumentException;
use RuntimeException;

class PhysicianOrdersController extends ApiController
{
    public function index()
    {
        $query = $this->fetchTable('PhysicianOrders')->find()->contain(['Episodes', 'Referrals']);
        $episodeId = $this->request->getQuery('episode_id');
        if ($episodeId !== null) {
            $query->where(['PhysicianOrders.episode_id' => $episodeId]);
        }

        return $this->respond([
            'success' => true,
            'data' => $query
                ->orderByAsc('order_scope')
                ->orderByDesc('version_number')
                ->all()
                ->toList(),
        ]);
    }

    public function update(int $id)
    {
        try {
            $order = (new HomeHealthWorkflowService())->updatePhysicianOrder($id, $this->body(), $this->identity());
        } catch (RuntimeException | InvalidArgumentException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $order,
        ]);
    }
}
