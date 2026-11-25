<?php

namespace App\Exceptions;

use DomainException;

class OutOfDeliveryRangeException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The delivery distance exceeds the highest active tier.');
    }
}
