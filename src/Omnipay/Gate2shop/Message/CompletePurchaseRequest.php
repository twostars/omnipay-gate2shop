<?php

namespace Omnipay\Gate2shop\Message;
use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Gate2shop Complete Purchase Request
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    public function getData()
    {
        // Mandatory fields.
        $this->validate(
            'ppp_status', 'PPP_TransactionID', 'totalAmount', 'currency', 'responsechecksum', 'advanceresponsechecksum',
            'merchant_site_id', 'requestVersion', 'message', 'payment_method', 'merchant_id', 'responseTimeStamp',
            'dynamicDescriptor', 'clientIp',
            'item_name_1', 'item_amount_1',
            // Not officially deemed mandatory, but required as part of the checksum.
            'Status');
            
        $expectedChecksum = $this->createAdvanceResponseChecksum();
        if ($this->getAdvanceResponseChecksum() !== $expectedChecksum) {
            throw new InvalidResponseException('Invalid advanceResponseChecksum');
        }

        return $this->httpRequest->request->all();
    }

    public function getResponseChecksum()
    {
        return $this->getParameter('responsechecksum');
    }
    
    public function setResponseChecksum($value)
    {
        return $this->setParameter('responsechecksum', $value);
    }
    
    public function getAdvanceResponseChecksum()
    {
        return $this->getParameter('advanceresponsechecksum');
    }

    public function setAdvanceResponseChecksum($value)
    {
        return $this->setParameter('advanceresponsechecksum', $value);
    }
    
    public function getResponseTimestamp()
    {
        return $this->getParameter('responseTimeStamp');
    }

    public function setResponseTimestamp($value)
    {
        return $this->setParameter('responseTimeStamp', $value);
    }

    public function getPPP_TransactionID()
    {
        return $this->getParameter('PPP_TransactionID');
    }
    
    public function setPPP_TransactionID($value)
    {
        return $this->setParameter('PPP_TransactionID', $value);
    }
    
    public function getPPPStatus()
    {
        return $this->getParameter('ppp_status');
    }
    
    public function setPPPStatus($value)
    {
        return $this->setParameter('ppp_status', $value);
    }

    public function getStatus()
    {
        return $this->getParameter('Status');
    }
    
    public function setStatus($value)
    {
        return $this->setParameter('Status', $value);
    }

    public function getProductId()
    {
        return $this->getParameter('productId');
    }

    public function setProductId($value)
    {
        return $this->setParameter('productId', $value);
    }
    
    public function getTotalAmount()
    {
        return $this->getParameter('totalAmount');
    }
    
    public function setTotalAmount($value)
    {
        return $this->setParameter('totalAmount', $value);
    }
    
    public function getMerchantSiteId()
    {
        return $this->getParameter('merchant_site_id');
    }
    
    public function setMerchantSiteId($value)
    {
        return $this->setParameter('merchant_site_id', $value);
    }
    
    public function getRequestVersion()
    {
        return $this->getParameter('requestVersion');
    }
    
    public function setRequestVersion($value)
    {
        return $this->setParameter('requestVersion', $value);
    }
    
    public function getMessage()
    {
        return $this->getParameter('message');
    }
    
    public function setMessage($value)
    {
        return $this->setParameter('message', $value);
    }
    
    public function getPaymentMethod()
    {
        return $this->getParameter('payment_method');
    }
    
    public function setPaymentMethod($value)
    {
        return $this->setParameter('payment_method', $value);
    }
    
    public function getMerchantID()
    {
        return $this->getParameter('merchant_id');
    }
    
    public function setMerchantID($value)
    {
        return $this->setParameter('merchant_id', $value);
    }
    
    public function getDynamicDescriptor()
    {
        return $this->getParameter('dynamicDescriptor');
    }
    
    public function setDynamicDescriptor($value)
    {
        return $this->setParameter('dynamicDescriptor', $value);
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
        $checksum .= $this->getTotalAmount();
        $checksum .= $this->getCurrency();
        $checksum .= $this->getResponseTimestamp();
        $checksum .= $this->getPPP_TransactionID();
        $checksum .= $this->getStatus();

        if (!empty($this->getProductId())) {
            $checksum .= $this->getProductId();
        } else {
            foreach ($this->getItems() as $item) {
                $checksum .= $item->getName();
            }
        }
        
        return md5($checksum);
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
