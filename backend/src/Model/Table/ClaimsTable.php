<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ClaimsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('claims');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Episodes');
    }
}
