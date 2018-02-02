<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;

/**
 * Description of Config
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Config
{

    /** @var  Store */
    private $store;

    public function __construct(
        Store $store
    )
    {
        $this->store = $store;
    }

    /**
     * @param Store $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    public function getApiToken()
    {
        return $this->store->getConfig('payment/paynl/apitoken');
    }

    public function getServiceId()
    {
        return $this->store->getConfig('payment/paynl/serviceid');
    }

    public function isTestMode()
    {
       return $this->store->getConfig('payment/paynl/testmode') == 1;
    }
	public function isNeverCancel()
	{
		return $this->store->getConfig('payment/paynl/never_cancel') == 1;
	}

	public function isAlwaysBaseCurrency(){
        return $this->store->getConfig('payment/paynl/always_base_currency') == 1;
    }

    public function getLanguage(){
        $language = $this->store->getConfig('payment/paynl/language');
        return $language?$language:'nl'; //default nl
    }

    public function getPaymentOptionId($methodCode){
        return $this->store->getConfig('payment/'.$methodCode.'/payment_option_id');
    }

    /**
     * Configures the sdk with the API token and serviceId
     *
     * @return bool TRUE when config loaded, FALSE when the apitoken or serviceId are empty
     */
    public function configureSDK(){
        $apiToken = $this->getApiToken();
        $serviceId = $this->getServiceId();

        if(!empty($apiToken) && !empty($serviceId)){
            \Paynl\Config::setApiToken($apiToken);
            \Paynl\Config::setServiceId($serviceId);
            return true;
        }
        return false;
    }
}