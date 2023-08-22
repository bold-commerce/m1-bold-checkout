<?php
// @phpcs:disable MEQP1.CodeAnalysis.EmptyBlock.DetectedCatch
$installer = $this;
// @phpcs:ignore MEQP1.Security.InsecureFunction.FoundWithAlternative
$constraintName = md5(
    'FK_' .
    $this->getTable(Bold_Checkout_Model_Order::RESOURCE) .
    '_ORDER_ID_' .
    $this->getTable('sales_flat_order') .
    '_ENTITY_ID'
);
$installer->run(
    "
create table if not exists {$this->getTable(Bold_Checkout_Model_Order::RESOURCE)}
(
    id int unsigned auto_increment comment 'Entity ID' primary key,
    order_id int unsigned not null comment 'Magento Sales Order ID',
    public_id varchar(256) not null comment 'Bold Checkout Public Order ID',
    financial_status varchar(20)  not null comment 'Bold Checkout Order Financial Status',
    fulfillment_status varchar(20)  not null comment 'Bold Checkout Order Fulfilment Status',
    constraint UNQ_BOLD_CHECKOUT_ORDER_ORDER_ID unique (order_id),
    constraint {$constraintName}
        foreign key (order_id) references {$this->getTable('sales_flat_order')} (entity_id)
            on update cascade on delete cascade
) engine=InnoDB DEFAULT charset=utf8 comment='Sales Order Additional Bold Data.';
"
);

try {
    $installer->run(
        "
create index IDX_BOLD_CHECKOUT_ORDER_ORDER_ID on {$this->getTable(Bold_Checkout_Model_Order::RESOURCE)} (order_id);
"
    );
} catch (Exception $exception) {
    // Index already exists, do nothing.
}

$installer->endSetup();
