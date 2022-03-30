<?php

namespace Drip\Payments\Controller;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class PostData extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
        Session $checkoutSession, 
        \Magento\Framework\Locale\Resolver $store,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, 
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig; //Used for getting data from System/Admin config
        $this->checkoutSession = $checkoutSession; //Used for getting the order: $order = $this->checkoutSession->getLastRealOrder(); And other order data like ID & amount
        $this->store = $store; //Used for getting store locale if needed $language_code = $this->store->getLocale();
        $this->urlBuilder = $urlBuilder; //Used for creating URLs to other custom controllers, for example $success_url = $this->urlBuilder->getUrl('frontname/path/action');
        $this->resultJsonFactory = $resultJsonFactory; //Used for returning JSON data to the afterPlaceOrder function ($result = $this->resultJsonFactory->create(); return $result->setData($post_data);)
        $this->curl = $curl;
    }

    public function execute()
    {
        $params = [
            'order' => $this->checkoutSession->getLastRealOrder(),
            'storeLocale' => $this->store->getLocale()
        ];
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("X-API-Key", "d87ae021-0750-416d-83cd-855b4844454e");
        $this->curl->post("http://localhost:8080/api/v1/checkouts", $params);
            
        //Your custom code for getting the data the payment provider needs
        //Structure your return data so the form-builder.js can build your form correctly
        $post_data = array(
            'action' => "formactionurl",
            'fields' => array (
                'shop_id' => "shop_id",
                'order_id' => "order_id",
                'api_key' => "api_key",
                //add all the fields you need
            )
        );
        $result = $this->resultJsonFactory->create();

        return $result->setData($post_data); //return data in JSON format
    }
}