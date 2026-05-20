<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class CheckInOutEventsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('check_in_out_events');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Visits');
    }
}
