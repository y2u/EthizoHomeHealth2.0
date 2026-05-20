<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

class EvvController extends ApiController
{
    public function index()
    {
        $records = $this->fetchTable('EvvRecords')->find()
            ->contain(['Visits'])
            ->orderByDesc('EvvRecords.created')
            ->all()
            ->toList();

        return $this->respond([
            'success' => true,
            'data' => $records,
        ]);
    }

    public function submit(int $id)
    {
        $records = $this->fetchTable('EvvRecords');
        $record = $records->get($id);
        $record = $records->patchEntity($record, [
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
            'submission_reference' => 'EVV-' . strtoupper(bin2hex(random_bytes(4))),
            'exception_reason' => null,
        ]);
        $records->saveOrFail($record);

        return $this->respond([
            'success' => true,
            'data' => $record,
        ]);
    }

    public function markException(int $id)
    {
        $records = $this->fetchTable('EvvRecords');
        $record = $records->get($id);
        $body = $this->body();
        $reason = trim((string)($body['exception_reason'] ?? ''));
        if ($reason === '') {
            return $this->respond([
                'success' => false,
                'message' => 'An EVV exception reason is required.',
            ], 422);
        }

        $record = $records->patchEntity($record, [
            'status' => 'exception',
            'exception_reason' => $reason,
            'reconciled_at' => null,
        ]);
        $records->saveOrFail($record);

        return $this->respond([
            'success' => true,
            'data' => $record,
        ]);
    }

    public function reconcile(int $id)
    {
        $records = $this->fetchTable('EvvRecords');
        $record = $records->get($id);
        if (!in_array((string)$record->get('status'), ['submitted', 'exception', 'reconciled'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Only submitted or exception EVV records can be reconciled.',
            ], 422);
        }

        $record = $records->patchEntity($record, [
            'status' => 'reconciled',
            'reconciled_at' => date('Y-m-d H:i:s'),
            'exception_reason' => null,
        ]);
        $records->saveOrFail($record);

        return $this->respond([
            'success' => true,
            'data' => $record,
        ]);
    }
}
