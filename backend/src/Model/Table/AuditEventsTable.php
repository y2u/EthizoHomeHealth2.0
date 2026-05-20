<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class AuditEventsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('audit_events');
    }
}
