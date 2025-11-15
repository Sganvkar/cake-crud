<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Customer extends Entity
{
    /**
     * Make only specific fields mass-assignable.
     */
    protected array $_accessible = [
        'BillTo'        => true,
        'CreatedDate'   => true,
        'CustomerCode'  => true,
        'CustomerName'  => true,
        'WarehouseCode' => true,
        // id => false (auto-increment)
    ];

    /**
     * Hide nothing — optional.
     */
    protected array $_hidden = [];
}
