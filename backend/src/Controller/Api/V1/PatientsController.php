<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

class PatientsController extends ApiController
{
    public function index()
    {
        $patients = $this->fetchTable('Patients')->find()->orderByAsc('last_name')->all()->toList();

        return $this->respond([
            'success' => true,
            'data' => $patients,
        ]);
    }

    public function view(int $id)
    {
        $patient = $this->fetchTable('Patients')->get($id, contain: ['Referrals', 'Episodes']);

        return $this->respond([
            'success' => true,
            'data' => $patient,
        ]);
    }

    public function add()
    {
        $patients = $this->fetchTable('Patients');
        $patient = $patients->newEntity($this->body());
        if ($patient->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $patient->getErrors(),
            ], 422);
        }

        $patients->saveOrFail($patient);

        return $this->respond([
            'success' => true,
            'data' => $patient,
        ], 201);
    }

    public function update(int $id)
    {
        $patients = $this->fetchTable('Patients');
        $patient = $patients->get($id);
        $patient = $patients->patchEntity($patient, $this->body());
        if ($patient->hasErrors()) {
            return $this->respond([
                'success' => false,
                'errors' => $patient->getErrors(),
            ], 422);
        }

        $patients->saveOrFail($patient);

        return $this->respond([
            'success' => true,
            'data' => $patient,
        ]);
    }
}
