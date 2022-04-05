<?php

namespace Drip\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Drip\Payments\Utils\RequestService;

class ConfigObserver implements ObserverInterface
{
    /**
     * Create Webhook
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/custompayment');

        if (!RequestService::checkActiveAndConfigValues($configs)) {
            return false;
        }
        $requestService = RequestService::createInstance($configs);

        $objectManager->get('Magento\Framework\App\Config\Storage\WriterInterface')->save('payment/custompayment/cnpj', $requestService->getCnpj(), 'default');
    }
}
