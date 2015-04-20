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
        // This is required to pass coding style standards.
        // Omnipay forces us to name it setPPP_TransactionId, but
        // the 'camel caps' rule strictly enforces no underscores.
        $this->setPPPTransactionId($this->httpRequest->get('PPP_TransactionID'));
        
        // Build a list of supplied items, as it's not handled very nicely;
        // we don't know how many items are provided, so we have to bruteforce it.
        $items = array();
        $n = 1;
        for ($n = 1; $this->httpRequest->get("item_name_$n"); ++$n) {
            $item = array(
                // The only two mandatory fields are item_name_X & item_amount_X.
                'name' => $this->httpRequest->get("item_name_$n"),
                'amount' => $this->httpRequest->get("item_amount_$n"),
                // The following are optional.
                'quantity' => $this->httpRequest->get("item_quantity_$n"),
                'discount' => $this->httpRequest->get("item_discount_$n"),
                'handling' => $this->httpRequest->get("item_handling_$n"),
                'shipping' => $this->httpRequest->get("item_shipping_$n"),
            );

            // Enforce the existing of mandatory fields.
            if (empty($item['name'])) {
                throw new InvalidResponseException('The name parameter is required.');
            }

            if (empty($item['amount'])) {
                throw new InvalidResponseException('The amount parameter is required.');
            }
            
            $items[] = $item;
        }

        // We require at least one item.
        if (empty($items)) {
            throw new InvalidResponseException('Item fields are mandatory.');
        }

        $this->setItems($items);

        // Mandatory fields.
        $this->validate(
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
            // Not officially deemed mandatory, but required as part of the checksum.
            'Status'
        );
            
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

    public function getPPPTransactionId()
    {
        return $this->getParameter('PPP_TransactionID');
    }
    
    public function setPPPTransactionId($value)
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
        $checksum .= $this->getPPPTransactionId();
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
