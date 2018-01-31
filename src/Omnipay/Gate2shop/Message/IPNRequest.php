<?php

namespace Omnipay\Gate2shop\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Gate2shop IPN Request
 */
class IPNRequest extends PurchaseRequest
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
            if (!isset($_GET[$key])) {
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
            'payment_method',
            'merchant_id',
            'responseTimeStamp',
            'dynamicDescriptor',
            'productId', /* not deemed mandatory but supplied as either the productId or list of item names */
            'item_amount_1' /* 1 item is required. item_name_1 is deemed mandatory also, but not actually supplied */
        );

        $expectedChecksum = $this->createResponseChecksum();
        if ($_GET['responsechecksum'] !== $expectedChecksum) {
            throw new InvalidResponseException('Invalid responsechecksum');
        }

        return $this->httpRequest->query->all();
    }

    public function createResponseChecksum()
    {
        $checksum = '';

        $checksum .= $this->getSecretKey();
        $checksum .= $_GET['ppp_status'];
        $checksum .= $_GET['PPP_TransactionID'];
        
        return md5($checksum);
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
