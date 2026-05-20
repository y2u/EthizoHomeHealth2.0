<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

class DashboardController extends ApiController
{
    public function index()
    {
        $todayStart = '2026-04-19 00:00:00';
        $todayEnd = '2026-04-20 00:00:00';
        $patients = $this->fetchTable('Patients')->find()->count();
        $referrals = $this->fetchTable('Referrals')->find()->count();
        $episodes = $this->fetchTable('Episodes')->find()->count();
        $visitsToday = $this->fetchTable('Visits')->find()
            ->where([
                'scheduled_start >=' => $todayStart,
                'scheduled_start <' => $todayEnd,
            ])
            ->count();
        $qaTasks = $this->fetchTable('QaTasks')->find()->where(['status' => 'open'])->count();
        $claimsOnHold = $this->fetchTable('Claims')->find()->where(['status IN' => ['draft', 'ready'], 'hold_reason IS NOT' => null])->count();

        $upcoming = $this->fetchTable('Episodes')->find()
            ->select(['id', 'episode_status', 'noa_due_date', 'start_of_care_date', 'pdgm_group_code'])
            ->where(['episode_status IN' => ['pending_admission', 'active']])
            ->orderByAsc('noa_due_date')
            ->limit(6)
            ->all()
            ->toList();

        return $this->respond([
            'success' => true,
            'metrics' => compact('patients', 'referrals', 'episodes', 'visitsToday', 'qaTasks', 'claimsOnHold'),
            'upcoming' => $upcoming,
        ]);
    }
}
