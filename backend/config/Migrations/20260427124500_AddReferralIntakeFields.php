<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReferralIntakeFields extends BaseMigration
{
    public function change(): void
    {
        $referrals = $this->table('referrals');

        if (!$referrals->hasColumn('admission_source')) {
            $referrals->addColumn('admission_source', 'string', ['limit' => 60, 'null' => true]);
        }

        if (!$referrals->hasColumn('referring_provider_name')) {
            $referrals->addColumn('referring_provider_name', 'string', ['limit' => 160, 'null' => true]);
        }

        if (!$referrals->hasColumn('referring_provider_phone')) {
            $referrals->addColumn('referring_provider_phone', 'string', ['limit' => 40, 'null' => true]);
        }

        if (!$referrals->hasColumn('pcp_name')) {
            $referrals->addColumn('pcp_name', 'string', ['limit' => 160, 'null' => true]);
        }

        if (!$referrals->hasColumn('pcp_phone')) {
            $referrals->addColumn('pcp_phone', 'string', ['limit' => 40, 'null' => true]);
        }

        if (!$referrals->hasColumn('caregiver_name')) {
            $referrals->addColumn('caregiver_name', 'string', ['limit' => 120, 'null' => true]);
        }

        if (!$referrals->hasColumn('caregiver_relationship')) {
            $referrals->addColumn('caregiver_relationship', 'string', ['limit' => 80, 'null' => true]);
        }

        if (!$referrals->hasColumn('caregiver_phone')) {
            $referrals->addColumn('caregiver_phone', 'string', ['limit' => 40, 'null' => true]);
        }

        if (!$referrals->hasColumn('service_location_type')) {
            $referrals->addColumn('service_location_type', 'string', ['limit' => 60, 'null' => true]);
        }

        if (!$referrals->hasColumn('service_address1')) {
            $referrals->addColumn('service_address1', 'string', ['limit' => 160, 'null' => true]);
        }

        if (!$referrals->hasColumn('service_city')) {
            $referrals->addColumn('service_city', 'string', ['limit' => 120, 'null' => true]);
        }

        if (!$referrals->hasColumn('service_state')) {
            $referrals->addColumn('service_state', 'string', ['limit' => 12, 'null' => true]);
        }

        if (!$referrals->hasColumn('service_postal_code')) {
            $referrals->addColumn('service_postal_code', 'string', ['limit' => 20, 'null' => true]);
        }

        $referrals->update();
    }
}
