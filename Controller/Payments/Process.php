<?php 

namespace Drip\Payments\Controller\Payments;

use Drip\Payments\Utils\RequestService;
use Exception;

class Process extends \Magento\Framework\App\Action\Action {
	
	public function __construct(
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Framework\App\Response\RedirectInterface $redirect,
		\Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseInterface $response,
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
	) {
		$this->invoiceService = $invoiceService;
		$this->_redirect = $redirect;
		$this->_request = $request;
        $this->_response = $response;
		$this->_pageFactory = $pageFactory;
		$this->_invoiceSender = $invoiceSender;
		return parent::__construct($context);
	}

	public function execute()
	{
		$checkoutId = $this->_request->getParam('checkoutId');
		if (!preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $checkoutId, $matches)) {
			return false;
		}
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/drip');

		if (!RequestService::checkActiveAndConfigValues($configs) || strlen($checkoutId) < 8) {
			return false;
		}

		$requestService = RequestService::createInstance($configs);

		try {
			$checkout = $requestService->getCheckout($checkoutId);
		} catch(Exception $e) {
			return false;
		}

		$orderId = $checkout->merchantCode;

		$processingStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;

		$order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($orderId);
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$redirectUrl = $storeManager->getStore()->getBaseUrl() . "checkout/onepage/failure";
		if ($order->getStatus() == 'pending') {
			if ($checkout->status == 'OK') {
				$order->setState($processingStatus)->setStatus($processingStatus);
				$order->setTotalPaid(number_format($order->getGrandTotal(), 2, '.', ''));
				$order->addStatusHistoryComment("Ordem #{$orderId} aprovada. (Checkout Drip #{$checkoutId}, Ordem Drip #{$checkout->orderId})")->setIsCustomerNotified(false);
				$order->save();
		
				$invoice = $this->invoiceService->prepareInvoice($order);        
				$invoice->setTransactionId($checkoutId);
				$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
				$invoice->register();
				$invoice->save();
				// send invoice email
				try {
					$this->_invoiceSender->send($invoice);
				} catch (\Exception $e) {
					$this->logger->error($e->getMessage());
				}
				$redirectUrl = $storeManager->getStore()->getBaseUrl() . "checkout/onepage/success";
			}
			if ($checkout->status == 'KO') {
				$canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
				$order->setState($canceledStatus)->setStatus($canceledStatus);
				$order->addStatusHistoryComment("Ordem #{$orderId} negada. (Checkout Drip #{$checkoutId})")->setIsCustomerNotified(true);            
				$order->save();
			}
		}
		return $this->_redirect->redirect($this->_response, $redirectUrl);
	}
}