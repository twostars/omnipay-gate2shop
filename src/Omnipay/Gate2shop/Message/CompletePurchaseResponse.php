<?php

namespace Omnipay\Gate2shop\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 *  Gate2shop Complete Purchase Response
 */
class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return true;
    }
}
