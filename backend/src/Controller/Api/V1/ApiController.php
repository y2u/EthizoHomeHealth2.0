<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use Cake\Http\Response;

class ApiController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->disableAutoRender();
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function respond(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStatus($status)
            ->withStringBody((string)json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        $data = $this->request->getData();
        if (is_array($data) && $data !== []) {
            return $data;
        }

        $raw = (string)$this->request->getBody();
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function identity(): array
    {
        /** @var array<string, mixed>|null $identity */
        $identity = $this->request->getAttribute('identity');

        return $identity ?? [];
    }
}
