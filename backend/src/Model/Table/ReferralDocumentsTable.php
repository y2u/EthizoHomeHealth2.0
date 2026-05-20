<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ReferralDocumentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('referral_documents');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Referrals');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('referral_id')->requirePresence('referral_id', 'create')->notEmptyString('referral_id')
            ->scalar('document_type')->requirePresence('document_type', 'create')->notEmptyString('document_type')
            ->scalar('document_status')->requirePresence('document_status', 'create')->notEmptyString('document_status')
            ->scalar('source_name')->allowEmptyString('source_name')
            ->dateTime('received_at')->allowEmptyDateTime('received_at')
            ->dateTime('signed_at')->allowEmptyDateTime('signed_at')
            ->scalar('original_file_name')->allowEmptyString('original_file_name')
            ->scalar('stored_file_name')->allowEmptyString('stored_file_name')
            ->scalar('mime_type')->allowEmptyString('mime_type')
            ->integer('file_size')->allowEmptyString('file_size')
            ->scalar('attachment_path')->allowEmptyString('attachment_path')
            ->scalar('document_note')->allowEmptyString('document_note');

        $validator->add('signed_at', 'signedTimestampRequired', [
            'rule' => function ($value, array $context): bool {
                $documentType = (string)($context['data']['document_type'] ?? '');
                $documentStatus = strtolower((string)($context['data']['document_status'] ?? ''));
                if ($documentType !== 'physician_orders' || $documentStatus !== 'signed') {
                    return true;
                }

                return !empty($value);
            },
            'message' => 'Signed physician order documents require a signed timestamp.',
        ]);

        return $validator;
    }
}
