<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\HomeHealthWorkflowService;
use InvalidArgumentException;
use RuntimeException;

class ReferralsController extends ApiController
{
    public function index()
    {
        $referrals = $this->fetchTable('Referrals')->find()
            ->contain(['Patients'])
            ->orderByDesc('planned_soc_date')
            ->all()
            ->toList();

        return $this->respond([
            'success' => true,
            'data' => $referrals,
        ]);
    }

    public function add()
    {
        $referrals = $this->fetchTable('Referrals');
        $data = $this->body();
        $data['requested_disciplines'] = json_encode($data['requested_disciplines'] ?? [], JSON_THROW_ON_ERROR);
        $referral = $referrals->newEntity($data);

        if ($referral->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $referral->getErrors(),
            ], 422);
        }

        $referrals->saveOrFail($referral);

        return $this->respond([
            'success' => true,
            'data' => $referral,
        ], 201);
    }

    public function update(int $id)
    {
        $data = $this->body();
        $data['requested_disciplines'] = $data['requested_disciplines'] ?? [];

        try {
            $referral = (new HomeHealthWorkflowService())->updateReferralDetails($id, $data, $this->identity());
        } catch (RuntimeException | InvalidArgumentException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $referral,
        ]);
    }

    public function convert(int $id)
    {
        try {
            $episode = (new HomeHealthWorkflowService())->convertReferralToEpisode($id, $this->identity());
        } catch (RuntimeException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $episode,
        ]);
    }

    public function updateIntakeDocs(int $id)
    {
        try {
            $referral = (new HomeHealthWorkflowService())->updateReferralIntakeDocumentation($id, $this->body(), $this->identity());
        } catch (RuntimeException | InvalidArgumentException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $referral,
        ]);
    }

    public function addDocument(int $id)
    {
        try {
            $document = (new HomeHealthWorkflowService())->addReferralDocument($id, $this->body(), $this->identity());
        } catch (RuntimeException | InvalidArgumentException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $document,
        ], 201);
    }
}
