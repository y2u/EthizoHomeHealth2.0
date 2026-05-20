<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddEpisodeAdmissionSnapshot extends BaseMigration
{
    public function change(): void
    {
        $episodes = $this->table('episodes');

        if (!$episodes->hasColumn('admission_readiness_snapshot')) {
            $episodes->addColumn('admission_readiness_snapshot', 'text', ['null' => true]);
        }

        $episodes->update();
    }
}
