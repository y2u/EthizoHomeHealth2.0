<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPatientIntakeFields extends BaseMigration
{
    public function change(): void
    {
        $patients = $this->table('patients');

        if (!$patients->hasColumn('gender')) {
            $patients->addColumn('gender', 'string', ['limit' => 20, 'null' => true]);
        }

        if (!$patients->hasColumn('insurance_member_id')) {
            $patients->addColumn('insurance_member_id', 'string', ['limit' => 60, 'null' => true]);
        }

        if (!$patients->hasColumn('emergency_contact_relationship')) {
            $patients->addColumn('emergency_contact_relationship', 'string', ['limit' => 80, 'null' => true]);
        }

        if (!$patients->hasColumn('responsible_party_name')) {
            $patients->addColumn('responsible_party_name', 'string', ['limit' => 120, 'null' => true]);
        }

        if (!$patients->hasColumn('responsible_party_relationship')) {
            $patients->addColumn('responsible_party_relationship', 'string', ['limit' => 80, 'null' => true]);
        }

        if (!$patients->hasColumn('responsible_party_phone')) {
            $patients->addColumn('responsible_party_phone', 'string', ['limit' => 40, 'null' => true]);
        }

        $patients->update();
    }
}
