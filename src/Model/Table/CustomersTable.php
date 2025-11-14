<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CustomersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // The physical DB table name
        $this->setTable('Customers');

        // Primary key — adjust if yours is different
        // Many SAP/Pronto systems use CustomerCode as PK
        $this->setPrimaryKey('CustomerCode');

        // (Optional) You may set displayField if needed for forms
        $this->setDisplayField('CustomerName');
    }

    public function validationDefault(Validator $validator): Validator
    {
        // All fields optional in this example since it's just a data fetch
        $validator
            ->allowEmptyString('BillTo')
            ->allowEmptyDate('CreatedDate')
            ->allowEmptyString('CustomerName')
            ->allowEmptyString('WarehouseCode');

        return $validator;
    }
}
