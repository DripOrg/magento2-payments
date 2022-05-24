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
        $orderRepository = $objectManager->get('Magento\Sales\Api\OrderRepositoryInterface');
        $searchCriteriaBuilder = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        $sortBuilder = $objectManager->get('\Magento\Framework\Api\SortOrderBuilder');

        $configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/drip');
        $cron_in_minutes = isset($configs["cron_for_cancel_orders"]) ? $configs["cron_for_cancel_orders"] : 15;

        if ($cron_in_minutes > 60) $cron_in_minutes = 60;
        if ($cron_in_minutes < 1) $cron_in_minutes = 1;

        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('status', 'pending', 'eq')
            ->addSortOrder($sortBuilder->setField('entity_id')
                ->setDescendingDirection()->create())
            ->setPageSize(100)->setCurrentPage(1)->create();

        $today = date("Y-m-d h:i:s");

        $to    = strtotime("-$cron_in_minutes min", strtotime($today));
        $to  = date('Y-m-d h:i:s', $to);

        $from  = strtotime('-2 day', strtotime($to));
        $from  = date('Y-m-d h:i:s', $from);

        $ordersList = $orderRepository->getList($searchCriteria);
        $ordersList->addFieldToFilter('created_at', array('from' => $from, 'to' => $to));

        foreach ($ordersList->getItems() as $order) {
            if ($order->getPayment()->getMethodInstance()->getCode() == 'drip') {
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->setState($canceledStatus)->setStatus($canceledStatus);
                $order->addStatusHistoryComment("Ordem expirada. Cancelada automaticamente");
                $order->save();
            }
        }
    }
}
