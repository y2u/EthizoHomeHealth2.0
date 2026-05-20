<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PhysicianOrdersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('physician_orders');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Referrals');
        $this->belongsTo('Episodes');
        $this->belongsTo('ReferralDocuments');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('episode_id')->requirePresence('episode_id', 'create')->notEmptyString('episode_id')
            ->scalar('order_scope')->requirePresence('order_scope', 'create')->notEmptyString('order_scope')
            ->integer('version_number')->requirePresence('version_number', 'create')->notEmptyString('version_number')
            ->scalar('order_status')->requirePresence('order_status', 'create')->notEmptyString('order_status')
            ->boolean('active')->allowEmptyString('active')
            ->dateTime('sent_at')->allowEmptyDateTime('sent_at')
            ->dateTime('received_at')->allowEmptyDateTime('received_at')
            ->dateTime('signed_at')->allowEmptyDateTime('signed_at')
            ->scalar('signer_name')->allowEmptyString('signer_name')
            ->scalar('order_summary')->allowEmptyString('order_summary')
            ->scalar('order_note')->allowEmptyString('order_note');

        $validator->add('signed_at', 'signedTimestampRequired', [
            'rule' => function ($value, array $context): bool {
                if (strtolower((string)($context['data']['order_status'] ?? '')) !== 'signed') {
                    return true;
                }

                return !empty($value);
            },
            'message' => 'Signed physician orders require a signed timestamp.',
        ]);

        return $validator;
    }
}
