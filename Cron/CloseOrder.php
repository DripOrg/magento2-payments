<?php

namespace Drip\Payments\Cron;

class CloseOrder
{
    /**
     * @throws \Exception
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/drip');

        $cron_in_minutes = isset($configs["cron_for_cancel_orders"]) ? $configs["cron_for_cancel_orders"] : 30;

        if ($cron_in_minutes > 120) $cron_in_minutes = 120;
        if ($cron_in_minutes < 30) $cron_in_minutes = 30;

        // NEED CHANT lteq TO USE CRON_IN_MINUTES
        $gteq = strtotime('-2 day');
        $lteq = strtotime("-$cron_in_minutes minute");

        $OrderFactory = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
        $orderCollection = $OrderFactory->create()->addFieldToSelect(array('*'));
        $orderCollection
            ->addFieldToFilter('status', array('in' => array('pending', 'pending_payment')))
            ->addFieldToFilter('created_at', ['lteq' => date('Y-m-d H:i:s', $lteq)])
            ->addFieldToFilter('created_at', ['gteq' => date('Y-m-d H:i:s', $gteq)]);

        foreach ($orderCollection as $order) {
            if ($order->getPayment()->getMethodInstance()->getCode() == 'drip') {
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->setState($canceledStatus)->setStatus($canceledStatus);
                $order->addStatusHistoryComment("Ordem expirada. Cancelada automaticamente");
                $order->save();
            }
        }
    }
}
