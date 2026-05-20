<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeedHomeHealthDemoData extends BaseMigration
{
    public function up(): void
    {
        $now = '2026-04-19 09:00:00';
        $passwordHash = '$2y$12$jGKam2dVVabPUIoZqH/4QuUzdCDq90gLV/ykGL2i7tp6My0UHTAdG';

        $this->table('users')->insert([
            [
                'id' => 1,
                'full_name' => 'Marina Intake',
                'email' => 'intake@harborhomehealth.test',
                'password_hash' => $passwordHash,
                'role' => 'Intake',
                'mobile' => '404-555-0199',
                'status' => 'active',
                'mfa_enabled' => true,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'full_name' => 'Nina Clinician',
                'email' => 'clinician@harborhomehealth.test',
                'password_hash' => $passwordHash,
                'role' => 'Clinician',
                'mobile' => '404-555-0177',
                'status' => 'active',
                'mfa_enabled' => false,
                'last_login_at' => '2026-04-19 08:30:00',
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'full_name' => 'Bianca Billing',
                'email' => 'billing@harborhomehealth.test',
                'password_hash' => $passwordHash,
                'role' => 'Billing',
                'mobile' => '404-555-0166',
                'status' => 'active',
                'mfa_enabled' => true,
                'last_login_at' => '2026-04-21 13:45:00',
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 4,
                'full_name' => 'Quinn QA Reviewer',
                'email' => 'qa@harborhomehealth.test',
                'password_hash' => $passwordHash,
                'role' => 'QA',
                'mobile' => '404-555-0155',
                'status' => 'active',
                'mfa_enabled' => true,
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();

        $this->table('patients')->insert([
            [
                'id' => 1,
                'first_name' => 'Eleanor',
                'last_name' => 'Bishop',
                'dob' => '1946-02-14',
                'gender' => 'Female',
                'medicare_number' => '1EG4TE5MK73',
                'insurance_member_id' => '1EG4TE5MK73',
                'phone' => '404-555-0101',
                'address1' => '125 Peachtree View',
                'city' => 'Atlanta',
                'state' => 'GA',
                'postal_code' => '30309',
                'emergency_contact_name' => 'Samuel Bishop',
                'emergency_contact_relationship' => 'Spouse',
                'emergency_contact_phone' => '404-555-0110',
                'payer_type' => 'Medicare',
                'primary_physician' => 'Dr. Hayes',
                'responsible_party_name' => 'Samuel Bishop',
                'responsible_party_relationship' => 'Spouse',
                'responsible_party_phone' => '404-555-0110',
                'status' => 'active',
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();

        $this->table('referrals')->insert([
            [
                'id' => 1,
                'patient_id' => 1,
                'source_name' => 'Northside Hospital',
                'admission_source' => 'Hospital discharge',
                'payer_type' => 'Medicare',
                'primary_diagnosis' => 'I50.32 Chronic diastolic heart failure',
                'requested_disciplines' => '["SN","PT"]',
                'order_status' => 'signed',
                'physician_orders_signed' => true,
                'physician_orders_signed_at' => '2026-04-16 14:30:00',
                'face_to_face_date' => '2026-04-15',
                'referring_provider_name' => 'Dr. Alexis Monroe',
                'referring_provider_phone' => '404-555-0133',
                'pcp_name' => 'Dr. Hayes',
                'pcp_phone' => '404-555-0144',
                'caregiver_name' => 'Samuel Bishop',
                'caregiver_relationship' => 'Spouse',
                'caregiver_phone' => '404-555-0110',
                'service_location_type' => 'Patient home',
                'service_address1' => '125 Peachtree View',
                'service_city' => 'Atlanta',
                'service_state' => 'GA',
                'service_postal_code' => '30309',
                'intake_ready' => true,
                'planned_soc_date' => '2026-04-19',
                'status' => 'accepted',
                'notes' => 'Hospital discharge referral ready for SOC.',
                'created_by' => 1,
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM referrals WHERE id = 1");
        $this->execute("DELETE FROM patients WHERE id = 1");
        $this->execute("DELETE FROM users WHERE id IN (1, 2, 3, 4)");
    }
}
