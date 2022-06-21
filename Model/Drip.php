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
     * {@inheritdoc}
     */
    protected $_isGateway = true;

    /**
     * {@inheritdoc}
     */
    protected $_canOrder = true;

    /**
     * {@inheritdoc}
     */
    protected $_canAuthorize = true;

    /**
     * {@inheritdoc}
     */
    protected $_canCapture = true;

    /**
     * {@inheritdoc}
     */
    protected $_canRefund = true;

    /**
     * {@inheritdoc}
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * {@inheritdoc}
     */
    protected $_canUseCheckout = true;

    /**
     * {@inheritdoc}
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * {@inheritdoc}
     */
    protected $_canReviewPayment = true;

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
