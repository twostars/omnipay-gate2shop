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
            'advanceresponsechecksum',
            'merchant_site_id',
            'requestVersion',
            'message',
            'payment_method',
            'merchant_id',
            'responseTimeStamp',
            'dynamicDescriptor',
            'clientIp',
            // Require at least one item.
            'item_name_1',
            'item_amount_1',
            // Not officially deemed mandatory, but required as part of the checksum.
            'Status'
        );

        $expectedChecksum = $this->createAdvanceResponseChecksum();
        if ($this->httpRequest->query->get('advanceresponsechecksum') !== $expectedChecksum) {
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
                    * productId (If this parameter was not sent to Gate2Shop, then concatenate all item names)
            2. Use MD5 hash on the result string of the concatenation. Use encoding passed to the PPP
               from the vendor site to create the MD5 hash. The default encoding is UTF-8 (unless the
               encoding PP input parameter specifies otherwise).
        */
        $checksum = '';

        $checksum .= $this->getSecretKey();
        $checksum .= $this->httpRequest->query->get('totalAmount');
        $checksum .= $this->httpRequest->query->get('Currency');
        $checksum .= $this->httpRequest->query->get('ResponseTimeStamp');
        $checksum .= $this->httpRequest->query->get('PPP_TransactionID');
        $checksum .= $this->httpRequest->query->get('Status');

        $productId = $this->httpRequest->query->get('productId');
        if (!empty($productId)) {
            $checksum .= $productId;
        } else {
            // We don't know how many items are provided, so we have to bruteforce it.
            $itemNames = '';
            for ($n = 1;; ++$n) {
                $itemName = $this->httpRequest->query->get("item_name_$n");
                if ($itemName === null) {
                    break;
                }

                // Enforce the existence of the two mandatory item fields.
                $this->httpGetValidate("item_name_$n", "item_amount_$n");
                $itemNames .= $itemName;
            }
        }
        
        return md5($checksum);
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
