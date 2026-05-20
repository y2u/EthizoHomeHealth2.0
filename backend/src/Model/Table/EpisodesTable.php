<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class EpisodesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('episodes');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Patients');
        $this->belongsTo('Referrals');
        $this->hasMany('EpisodePeriods');
        $this->hasMany('Assessments');
        $this->hasMany('Visits');
        $this->hasMany('Claims');
        $this->hasMany('QaTasks');
    }
}
