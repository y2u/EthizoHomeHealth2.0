<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\TokenService;
use Cake\Utility\Security;

class AuthController extends ApiController
{
    public function login()
    {
        $data = $this->body();
        $user = $this->fetchTable('Users')->find()
            ->where(['email' => $data['email'] ?? ''])
            ->first();

        if ($user === null && ($data['email'] ?? '') === 'intake@harborhomehealth.test' && ($data['password'] ?? '') === 'demo1234') {
            return $this->demoIdentityResponse([
                'id' => 1,
                'full_name' => 'Marina Intake',
                'email' => 'intake@harborhomehealth.test',
                'role' => 'Intake',
            ]);
        }

        if ($user === null || !password_verify((string)($data['password'] ?? ''), (string)$user->get('password_hash'))) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ((string)$user->get('status') === 'suspended') {
            return $this->respond([
                'success' => false,
                'message' => 'This user account is suspended.',
            ], 403);
        }

        $token = (new TokenService(Security::getSalt()))->issue($user->toArray());
        $user = $this->fetchTable('Users')->patchEntity($user, ['last_login_at' => date('Y-m-d H:i:s')]);
        $this->fetchTable('Users')->save($user);

        return $this->respond([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->get('id'),
                'full_name' => $user->get('full_name'),
                'email' => $user->get('email'),
                'role' => $user->get('role'),
            ],
        ]);
    }

    public function demo()
    {
        $user = $this->fetchTable('Users')->find()->where(['email' => 'intake@harborhomehealth.test'])->first();
        if ($user === null) {
            return $this->demoIdentityResponse([
                'id' => 1,
                'full_name' => 'Marina Intake',
                'email' => 'intake@harborhomehealth.test',
                'role' => 'Intake',
            ]);
        }

        return $this->loginWith('intake@harborhomehealth.test');
    }

    public function me()
    {
        return $this->respond([
            'success' => true,
            'user' => $this->identity(),
        ]);
    }

    private function loginWith(string $email)
    {
        $user = $this->fetchTable('Users')->find()->where(['email' => $email])->firstOrFail();
        $token = (new TokenService(Security::getSalt()))->issue($user->toArray());

        return $this->respond([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->get('id'),
                'full_name' => $user->get('full_name'),
                'email' => $user->get('email'),
                'role' => $user->get('role'),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $identity
     */
    private function demoIdentityResponse(array $identity)
    {
        return $this->respond([
            'success' => true,
            'token' => (new TokenService(Security::getSalt()))->issue($identity),
            'user' => $identity,
        ]);
    }
}
