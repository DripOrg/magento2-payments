<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Drip\Payments\Block\Form;

/**
 * Block for Custom payment method form
 */
class Custompayment extends \Magento\OfflinePayments\Block\Form\AbstractInstruction
{
    /**
     * Custom payment template
     *
     * @var string
     */
    protected $_template = 'Drip_Payments::form/custompayment.phtml';
}
