<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class EpisodePeriodsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('episode_periods');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Episodes');
    }
}
