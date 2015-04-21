<?php

namespace Omnipay\Gate2shop\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Gate2shop\Message\PurchaseRequest;

/**
 * Gate2shop Complete Purchase Request
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    /**
     * Validate the request.
     *
     * This method is called internally by gateways to avoid wasting time with an API call
     * when the request is clearly invalid.
     *
     * @param string ... a variable length list of required parameters
     * @throws InvalidRequestException
     */
    public function httpGetValidate()
    {
        foreach (func_get_args() as $key) {
            $value = $this->httpRequest->query->get($key);
            if (empty($value)) {
                throw new InvalidRequestException("The $key GET parameter is required");
            }
        }
    }

    public function getData()
    {
        // Mandatory fields.
        $this->httpGetValidate(
            'ppp_status',
            'PPP_TransactionID',
            'totalAmount',
            'currency',
            'responsechecksum',
            'advanceResponseChecksum',
            'merchant_site_id',
            'requestVersion',
            'message',
            'payment_method',
            'merchant_id',
            'responseTimeStamp',
            'dynamicDescriptor',
            'productId', /* not deemed mandatory but supplied as either the productId or list of item names */
            'item_amount_1', /* 1 item is required. item_name_1 is deemed mandatory also, but not actually supplied */
            'Status' /* not deemed mandatory but used as part of the checksum */
        );

        $expectedChecksum = $this->createAdvanceResponseChecksum();
        if ($this->httpRequest->query->get('advanceResponseChecksum') !== $expectedChecksum) {
            throw new InvalidResponseException('Invalid advanceResponseChecksum');
        }

        return $this->httpRequest->query->all();
    }

    public function createAdvanceResponseChecksum()
    {
        /*
        To create a DMN response checksum:
            1. Concatenate the following parameters in the exact order listed below:
                a. Your secret key
                b. From the DMN callback:
                    * totalAmount
                    * Currency
                    * responseTimeStamp
                    * PPP_TransactionID
                    * Status
                    * productId (If this parameter was not sent to Gate2Shop, then [Gate2shop will] concatenate all item names)
            2. Use MD5 hash on the result string of the concatenation. Use encoding passed to the PPP
               from the vendor site to create the MD5 hash. The default encoding is UTF-8 (unless the
               encoding PP input parameter specifies otherwise).
        */
        $checksum = '';

        $checksum .= $this->getSecretKey();
        $checksum .= $this->httpRequest->query->get('totalAmount');
        $checksum .= $this->httpRequest->query->get('currency');
        $checksum .= $this->httpRequest->query->get('responseTimeStamp');
        $checksum .= $this->httpRequest->query->get('PPP_TransactionID');
        $checksum .= $this->httpRequest->query->get('Status');
        $checksum .= $this->httpRequest->query->get('productId');
        
        return md5($checksum);
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
