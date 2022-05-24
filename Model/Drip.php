<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Drip\Payments\Model;

/**
 * Custom payment method model
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 *
 * @api
 * @since 0.1.0
 */
class Drip extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CUSTOM_PAYMENT_CODE = 'drip';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::CUSTOM_PAYMENT_CODE;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = \Magento\Payment\Block\Info\Instructions::class;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
