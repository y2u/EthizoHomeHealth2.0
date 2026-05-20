<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\ClaimHoldService;
use App\Service\HomeHealthWorkflowService;

class ClaimsController extends ApiController
{
    public function index()
    {
        $claims = $this->fetchTable('Claims')->find()
            ->contain(['Episodes'])
            ->orderByDesc('Claims.created')
            ->all()
            ->toList();

        return $this->respond([
            'success' => true,
            'data' => $claims,
        ]);
    }

    public function submit(int $id)
    {
        $claims = $this->fetchTable('Claims');
        $claim = $claims->get($id);
        $episodeId = (int)$claim->get('episode_id');
        (new ClaimHoldService())->syncEpisodeClaimHolds($episodeId);
        $claim = $claims->get($id);
        $openQaTasks = $this->fetchTable('QaTasks')->find()
            ->where(['episode_id' => $episodeId, 'status' => 'open'])
            ->count();

        if ($openQaTasks > 0) {
            $claim = $claims->patchEntity($claim, ['status' => 'draft']);
            $claims->saveOrFail($claim);

            return $this->respond([
                'success' => false,
                'message' => 'Claim remains on hold until QA tasks are resolved.',
                'data' => $claim,
            ], 422);
        }

        $billingReadiness = (new HomeHealthWorkflowService())->evaluateBillingReadiness($episodeId);
        if ($billingReadiness['ready_to_bill'] !== true) {
            $claim = $claims->patchEntity($claim, [
                'status' => 'draft',
                'hold_reason' => implode(' | ', $billingReadiness['blockers']),
            ]);
            $claims->saveOrFail($claim);

            return $this->respond([
                'success' => false,
                'message' => (string)$billingReadiness['primary_blocker'],
                'data' => $claim,
            ], 422);
        }

        $claim = $claims->patchEntity($claim, [
            'status' => 'submitted',
            'hold_reason' => null,
            'submitted_at' => date('Y-m-d H:i:s'),
            'submission_reference' => 'CLM-' . strtoupper(bin2hex(random_bytes(4))),
        ]);
        $claims->saveOrFail($claim);

        return $this->respond([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function accept(int $id)
    {
        $claims = $this->fetchTable('Claims');
        $claim = $claims->get($id);
        if (!in_array((string)$claim->get('status'), ['submitted', 'accepted'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Only submitted claims can be marked accepted.',
            ], 422);
        }

        $body = $this->body();
        $claim = $claims->patchEntity($claim, [
            'status' => 'accepted',
            'accepted_at' => $body['accepted_at'] ?? date('Y-m-d H:i:s'),
            'payer_claim_number' => $body['payer_claim_number'] ?? $claim->get('payer_claim_number'),
            'rejected_at' => null,
            'rejection_reason' => null,
            'voided_at' => null,
            'void_reason' => null,
        ]);
        $claims->saveOrFail($claim);

        return $this->respond([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function reject(int $id)
    {
        $claims = $this->fetchTable('Claims');
        $claim = $claims->get($id);
        if (!in_array((string)$claim->get('status'), ['submitted', 'accepted'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Only submitted or accepted claims can be rejected.',
            ], 422);
        }

        $body = $this->body();
        $reason = trim((string)($body['rejection_reason'] ?? ''));
        if ($reason === '') {
            return $this->respond([
                'success' => false,
                'message' => 'A rejection reason is required.',
            ], 422);
        }

        $claim = $claims->patchEntity($claim, [
            'status' => 'rejected',
            'rejected_at' => $body['rejected_at'] ?? date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'accepted_at' => null,
            'paid_at' => null,
            'payment_amount' => null,
            'remittance_reference' => null,
        ]);
        $claims->saveOrFail($claim);

        return $this->respond([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function postPayment(int $id)
    {
        $claims = $this->fetchTable('Claims');
        $claim = $claims->get($id);
        if (!in_array((string)$claim->get('status'), ['submitted', 'accepted', 'paid'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Only submitted or accepted claims can post payment.',
            ], 422);
        }

        $body = $this->body();
        $paymentAmount = $body['payment_amount'] ?? $claim->get('amount');
        if ($paymentAmount === null || $paymentAmount === '') {
            return $this->respond([
                'success' => false,
                'message' => 'A payment amount is required.',
            ], 422);
        }

        $claim = $claims->patchEntity($claim, [
            'status' => 'paid',
            'paid_at' => $body['paid_at'] ?? date('Y-m-d H:i:s'),
            'payment_amount' => $paymentAmount,
            'remittance_reference' => $body['remittance_reference'] ?? $claim->get('remittance_reference'),
            'accepted_at' => $claim->get('accepted_at') ?? ($body['accepted_at'] ?? date('Y-m-d H:i:s')),
            'rejected_at' => null,
            'rejection_reason' => null,
            'voided_at' => null,
            'void_reason' => null,
        ]);
        $claims->saveOrFail($claim);

        return $this->respond([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function void(int $id)
    {
        $claims = $this->fetchTable('Claims');
        $claim = $claims->get($id);
        if (!in_array((string)$claim->get('status'), ['submitted', 'accepted', 'paid', 'rejected'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Only active claim records can be voided.',
            ], 422);
        }

        $body = $this->body();
        $reason = trim((string)($body['void_reason'] ?? ''));
        if ($reason === '') {
            return $this->respond([
                'success' => false,
                'message' => 'A void reason is required.',
            ], 422);
        }

        $claim = $claims->patchEntity($claim, [
            'status' => 'voided',
            'voided_at' => $body['voided_at'] ?? date('Y-m-d H:i:s'),
            'void_reason' => $reason,
        ]);
        $claims->saveOrFail($claim);

        return $this->respond([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function resubmitCorrected(int $id)
    {
        $claims = $this->fetchTable('Claims');
        $claim = $claims->get($id);
        if (!in_array((string)$claim->get('status'), ['rejected', 'voided'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Only rejected or voided claims can create a corrected claim.',
            ], 422);
        }

        $body = $this->body();
        $reason = trim((string)($body['correction_reason'] ?? $body['rejection_reason'] ?? $body['void_reason'] ?? ''));
        if ($reason === '') {
            return $this->respond([
                'success' => false,
                'message' => 'A correction reason is required to create a corrected claim.',
            ], 422);
        }

        $correctedClaim = $claims->newEntity([
            'episode_id' => $claim->get('episode_id'),
            'claim_type' => $claim->get('claim_type'),
            'billing_period_start' => $claim->get('billing_period_start'),
            'billing_period_end' => $claim->get('billing_period_end'),
            'status' => 'draft',
            'amount' => $body['amount'] ?? $claim->get('amount'),
            'hold_reason' => null,
            'submission_reference' => null,
            'submitted_at' => null,
            'payer_claim_number' => null,
            'accepted_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'payment_amount' => null,
            'remittance_reference' => null,
            'paid_at' => null,
            'voided_at' => null,
            'void_reason' => null,
            'corrected_from_claim_id' => $claim->get('id'),
            'correction_reason' => $reason,
        ]);
        $claims->saveOrFail($correctedClaim);

        return $this->respond([
            'success' => true,
            'data' => $correctedClaim,
        ], 201);
    }
}
