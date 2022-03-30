<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Drip\Payments\Model;

use DateInterval;
use DateTime;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Drip\Payments\Utils\RequestService;

class InstructionsConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        Custompayment::CUSTOM_PAYMENT_CODE,
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['dripPaymentsIframeUrl'] = 'https://drip-fe.usedrip.com.br/instalments_simulator?amount=totalOrderamount&date=actualDate';
                $config['payment']['dripPaymentsActualCashbackRate'] = $this->getActualCashbackRatio(); 
                $config['payment']['dripPaymentsIsDisabled'] = $this->getPluginIsDisabled(); 

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config['payment']['dripPaymentsActualCnpj'] = 
                    $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/custompayment/cnpj');
            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    private function getActualCashbackRatio() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/custompayment');
        $now = new DateTime();
        if(isset($configs['cashback'])) {
            $actualCashbackCache = (array) json_decode($configs['cashback']);
            $expirationTime = new Datetime($actualCashbackCache['expiration']->date);

            if($expirationTime > $now) {
                return $actualCashbackCache['value'];
            }
        }

		$requestService = RequestService::createInstance($configs);
        $actualCashback = $requestService->getCashback();
        
        $configs['cashback'] = json_encode([
            'value' => $actualCashback,
            'expiration' => $now->add(new DateInterval('PT5M'))
        ]);

        $objectManager->get('Magento\Framework\App\Config\Storage\WriterInterface')->save('payment/custompayment/cashback', $configs['cashback'], 'default');

        return $actualCashback;
    }

    private function getPluginIsDisabled() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/custompayment');
        $now = new DateTime();
        if(isset($configs['isDisabled'])) {
            $actualIsDisabledCache = (array) json_decode($configs['isDisabled']);
            $expirationTime = new Datetime($actualIsDisabledCache['expiration']->date);

            if($expirationTime > $now) {
                return $actualIsDisabledCache['value'];
            }
        }

		$requestService = RequestService::createInstance($configs);
        $actualIsDisabled = $requestService->isDisabled();
        
        $configs['isDisabled'] = json_encode([
            'value' => $actualIsDisabled,
            'expiration' => $now->add(new DateInterval('PT5M'))
        ]);

        $objectManager->get('Magento\Framework\App\Config\Storage\WriterInterface')->save('payment/custompayment/isDisabled', $configs['isDisabled'], 'default');

        return $actualIsDisabled;
    }
}
