<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddReferralOrderSignatureFields extends BaseMigration
{
    public function change(): void
    {
        $referrals = $this->table('referrals');

        if (!$referrals->hasColumn('physician_orders_signed')) {
            $referrals->addColumn('physician_orders_signed', 'boolean', ['default' => false]);
        }

        if (!$referrals->hasColumn('physician_orders_signed_at')) {
            $referrals->addColumn('physician_orders_signed_at', 'datetime', ['null' => true]);
        }

        $referrals->update();
    }
}
