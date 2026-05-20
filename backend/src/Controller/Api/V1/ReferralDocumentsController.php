<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\HomeHealthWorkflowService;
use Cake\Http\Exception\NotFoundException;
use InvalidArgumentException;
use RuntimeException;

class ReferralDocumentsController extends ApiController
{
    public function index()
    {
        $query = $this->fetchTable('ReferralDocuments')->find()->contain(['Referrals']);
        $referralId = $this->request->getQuery('referral_id');
        if ($referralId !== null) {
            $query->where(['ReferralDocuments.referral_id' => $referralId]);
        }

        return $this->respond([
            'success' => true,
            'data' => $query->orderByDesc('ReferralDocuments.created')->all()->toList(),
        ]);
    }

    public function update(int $id)
    {
        try {
            $document = (new HomeHealthWorkflowService())->updateReferralDocument($id, $this->body(), $this->identity());
        } catch (RuntimeException | InvalidArgumentException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $document,
        ]);
    }

    public function uploadAttachment(int $id)
    {
        try {
            $document = (new HomeHealthWorkflowService())->attachReferralDocumentFile(
                $id,
                $this->request->getUploadedFile('attachment'),
                $this->identity(),
            );
        } catch (RuntimeException | InvalidArgumentException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond([
            'success' => true,
            'data' => $document,
        ]);
    }

    public function download(int $id)
    {
        $document = $this->fetchTable('ReferralDocuments')->get($id);
        $path = (string)$document->get('attachment_path');
        if ($path === '' || !is_file($path)) {
            throw new NotFoundException('Attachment not found for this referral document.');
        }

        $downloadName = (string)($document->get('original_file_name') ?: basename($path));
        $mimeType = (string)($document->get('mime_type') ?: 'application/octet-stream');

        return $this->response
            ->withType($mimeType)
            ->withFile($path, [
                'download' => true,
                'name' => $downloadName,
            ]);
    }
}
