<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class QaTasksTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('qa_tasks');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Episodes');
        $this->belongsTo('Visits');
        $this->belongsTo('Assessments');
    }
}
