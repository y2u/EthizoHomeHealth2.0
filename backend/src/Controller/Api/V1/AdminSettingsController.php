<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\AuditLogger;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

class AdminSettingsController extends ApiController
{
    public function view()
    {
        return $this->respond([
            'success' => true,
            'data' => $this->findOrCreateSettings(),
        ]);
    }

    public function update()
    {
        $settings = $this->findOrCreateSettings();
        $payload = $this->body();
        $patch = array_intersect_key($payload, array_flip([
            'require_mfa',
            'session_timeout_minutes',
            'remember_device_days',
            'password_rotation_days',
            'attachment_retention_days',
            'allowed_ip_ranges',
            'enforce_device_attestation',
        ]));

        $table = $this->settingsTable();
        $settings = $table->patchEntity($settings, $patch);
        $table->saveOrFail($settings);

        (new AuditLogger($this->fetchTable('AuditEvents')))->log(
            $this->identity(),
            'admin_settings_updated',
            'AppSetting',
            (int)$settings->get('id'),
            $patch,
        );

        return $this->respond([
            'success' => true,
            'data' => $settings,
        ]);
    }

    private function settingsTable(): Table
    {
        return $this->fetchTable('AppSettings');
    }

    private function findOrCreateSettings(): EntityInterface
    {
        $table = $this->settingsTable();
        $settings = $table->find()->orderByAsc('id')->first();
        if ($settings !== null) {
            return $settings;
        }

        $settings = $table->newEntity([
            'require_mfa' => false,
            'session_timeout_minutes' => 30,
            'remember_device_days' => 14,
            'password_rotation_days' => 90,
            'attachment_retention_days' => 365,
            'allowed_ip_ranges' => null,
            'enforce_device_attestation' => false,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s'),
        ]);
        $table->saveOrFail($settings);

        return $settings;
    }
}
