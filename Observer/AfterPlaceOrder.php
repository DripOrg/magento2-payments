<?php

namespace Drip\Payments\Observer;

use Magento\Framework\Event\ObserverInterface;
use Drip\Payments\Utils\RequestService;
use Exception;

class AfterPlaceOrder implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->curl = $curl;
        $this->_redirect = $redirect;
        $this->_response = $response;
        $this->_storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getState() == 'new') {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/custompayment');
            if (!RequestService::checkActiveAndConfigValues($configs)) {
                return false;
            }
            
            $requestService = RequestService::createInstance($configs);

            $checkoutData = $this->createCheckoutData($order);
            $response = $requestService->createCheckout($checkoutData);
            if ($response->getStatusCode() === 201) {
                $responseBody = json_decode($response->getBody());
                $redirectUrl = $responseBody->formUrl . "?phone=" . preg_replace('/\D/', '', $checkoutData['customerPhone']);
                $order->setExtOrderId($responseBody->id);
                $order->save();
                return $this->_redirect->redirect($this->_response, $redirectUrl);
            }
            $redirectUrl = $this->_storeManager->getStore()->getBaseUrl() . 'sales/order/view/order_id/' . $order->getId();
            return $this->_redirect->redirect($this->_response, $redirectUrl);
        }
    }

    private function createCheckoutData($order)
    {
        $shippingAddressData = $order->getShippingAddress()->getData();

        return [
            'amount' => number_format($order->getGrandTotal(), 2, '.', ''),
            'customerCpf' => $this->getCustomerCpfFromOrder($order),
            'customerName' => $shippingAddressData['firstname'] . ' ' . $shippingAddressData['lastname'],
            'customerEmail' => $shippingAddressData['email'],
            'customerAddressCep' => $shippingAddressData['postcode'],
            'customerAddressNumber' => 0000,
            'customerAddressState' => $shippingAddressData['region'],
            'customerAddressCity' => $shippingAddressData['city'],
            'customerAddressStreet' => $shippingAddressData['street'],
            'customerAddressNeighborhood' => '',
            'customerPhone' => $shippingAddressData['telephone'],
            'merchantCode' => $order->getId(),
            'resolveUrl' => $this->_storeManager->getStore()->getBaseUrl() . 'drip/payments/process',
            'products' => $this->createProductsListFromOrder($order)
        ];
    }

    private function createProductsListFromOrder($order)
    {
        $orderProducts = [];

        //Add shipping to products list
        $totalShippingAmountValue = $order->getShippingAmount() != null ? number_format($order->getShippingAmount(), 2, '.', '') : '0';
        $orderProducts[] = [
            'name' => 'shipping',
            'quantity' => 1,
            'amount' => $totalShippingAmountValue,
            'totalAmount' => $totalShippingAmountValue
        ];

        $items = $order->getAllItems();
        foreach ($items as $item) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productModel = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getId());

            $itemArray = $item->getData();

            $totalSales = $productModel->getOrderedQty();
            $stockQuantity = 0;
            try {
                $stockQuantity = $productModel->getExtensionAttributes()->getStockItem()->getQty();
            } catch (Exception $e) {

            }
            

            $backOrders = 0;
            if ($totalSales > $stockQuantity) {
                $backOrders = $totalSales - $stockQuantity;
            }

            $orderProducts[] = [
                'id' => $itemArray['item_id'],
                'name' => $itemArray['name'],
                'created' => $itemArray['created_at'],
                'modified' => $itemArray['updated_at'],
                'featured' => '',
                'description' => $itemArray['description'],
                'link' => $productModel->getProductUrl(),
                'quantity' => intval($itemArray['qty_ordered']),
                'amount' => number_format($itemArray['price'], 2, '.', ''),
                'fullAmount' => number_format($itemArray['original_price'], 2, '.', ''),
                'totalSales' => $totalSales,
                'stockQuantity' => $stockQuantity,
                'backorders' => $backOrders,
                'attributes' => '',
                'categories' => '',
                'principalImage' => $productModel->getImageUrl(),
                'ratingCount' => '',
                'averageRating' => '',
                'totalAmount' => number_format($itemArray['row_total'], 2, '.', ''),
                'productDetails'=> ''
            ];
        }
        return $orderProducts;
    }

    private function getCustomerCpfFromOrder($order)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $customer = $objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface')->getById($order->getCustomerId());
        } catch (Exception $e) {

        }
        if (isset($customer)) {
            $cpf = $customer->getCustomAttribute('cpf') != null ? $customer->getCustomAttribute('cpf')->getValue() : $order->getCustomerTaxvat();
            $cpf = strlen($cpf) > 5 ? preg_replace('/[^0-9]/', '', $cpf) : null;
            return $cpf;
        }
        return strlen($order->getCustomerTaxvat()) > 5 ? preg_replace('/[^0-9]/', '', $order->getCustomerTaxvat()) : null;
    }
}
