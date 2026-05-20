<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class EvvRecordsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('evv_records');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Visits');
    }
}
