<?php

namespace Drip\Payments\Cron;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;

class CloseOrder
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroup
     */
    private $filterGroup;

    /**
     * CancelOrderPending constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroup $filterGroup
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroup $filterGroup
    ) {
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
        $this->filterGroup           = $filterGroup;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $configs = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/drip');
        $cron_in_minutes = isset($configs["cron_for_cancel_orders"]) ? $configs["cron_for_cancel_orders"] : 15;

        if ($cron_in_minutes > 60) $cron_in_minutes = 60;
        if ($cron_in_minutes < 1) $cron_in_minutes = 1;

        $today          = date("Y-m-d h:i:s");
        $to             = strtotime("-$cron_in_minutes min", strtotime($today));
        $to             = date('Y-m-d h:i:s', $to);

        $filterGroupDate      = $this->filterGroup;
        $filterGroupStatus    = clone ($filterGroupDate);

        $filterDate      = $this->filterBuilder
            ->setField('updated_at')
            ->setConditionType('to')
            ->setValue($to)
            ->create();
        $filterStatus    = $this->filterBuilder
            ->setField('status')
            ->setConditionType('eq')
            ->setValue('pending')
            ->create();

        $filterGroupDate->setFilters([$filterDate]);
        $filterGroupStatus->setFilters([$filterStatus]);

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups(
            [$filterGroupDate, $filterGroupStatus]
        );
        $searchResults  = $this->orderRepository->getList($searchCriteria->create());

        foreach ($searchResults->getItems() as $order) {
            if ($order->getPayment()->getMethodInstance()->getCode() == 'drip') {
                $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->setState($canceledStatus)->setStatus($canceledStatus);
                $order->addStatusHistoryComment("Ordem expirada. Cancelada automaticamente");
                $order->save();
            }
        }
    }
}
