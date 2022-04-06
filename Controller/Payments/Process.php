<?php

namespace Drip\Payments\Controller\Payments;

use Drip\Payments\Utils\RequestService;
use Exception;

class Process extends \Magento\Framework\App\Action\Action
{
    
    /**
     * @param InvoiceService $invoiceService
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->invoiceService = $invoiceService;
        $this->_request = $request;
        $this->_response = $response;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    /**
     * Process order
     */
    public function execute()
    {
        $checkoutId = $this->_request->getParam('checkoutId');
        if (!preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $checkoutId, $matches)) {
            return false;
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $configs = $objectManager->get(Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue('payment/custompayment');

        if (!RequestService::checkActiveAndConfigValues($configs) || strlen($checkoutId) < 8) {
            return false;
        }

        $requestService = RequestService::createInstance($configs);

        try {
            $checkout = $requestService->getCheckout($checkoutId);
        } catch (Exception $e) {
            return false;
        }

        $orderId = $checkout->merchantCode;

        $processingStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;

        $order = $objectManager->create(Magento\Sales\Api\Data\OrderInterface::class)
            ->load($orderId);
        if ($order->getStatus() == 'pending') {
            if ($checkout->status == 'OK') {
                $order->setState($processingStatus)->setStatus($processingStatus);
                $order->setTotalPaid(number_format($order->getGrandTotal(), 2, '.', ''));
                $order->addStatusHistoryComment(
                    "Ordem #{$orderId} aprovada. (Checkout Drip #{$checkoutId}, 
                    Ordem Drip #{$checkout->orderId})"
                )->setIsCustomerNotified(false);
                $order->save();
        
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setTransactionId($checkoutId);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();
            }
            if ($checkout->status == 'KO') {
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->setState($canceledStatus)->setStatus($canceledStatus);
                $order->addStatusHistoryComment(
                    "Ordem #{$orderId} negada. (Checkout Drip #{$checkoutId})"
                )->setIsCustomerNotified(true);
                $order->save();
            }
        }
        //$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        //$redirectUrl = $storeManager->getStore()->getBaseUrl() . "sales/order/view/order_id/$orderId";
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath("sales/order/view/order_id/$orderId");
    }
}
