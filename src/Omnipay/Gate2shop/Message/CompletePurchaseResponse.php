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
        return $this->getErrCode() === '0';
    }

    public function getMessage()
    {
        // message & Error fields are generic enough to be useless for most cases.
        // Reason, however, consistently provides more informative messages.
        if (isset($this->data['Reason']))
            return $this->data['Reason'];
    }

    public function getCode()
    {
        // OK, PENDING, or FAIL.
        if (isset($this->data['ppp_status']))
            return $this->data['ppp_status'];
    }

    public function getErrCode()
    {
        if (isset($this->data['ErrCode']))
            return $this->data['ErrCode'];
    }

    public function getExErrCode()
    {
        if (isset($this->data['ExErrCode']))
            return $this->data['ExErrCode'];
    }

    public function getTransactionReference()
    {
        if (isset($this->data['PPP_TransactionID']))
            return $this->data['PPP_TransactionID'];
    }
}
