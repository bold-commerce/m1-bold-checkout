<?php
$installer = $this;
$installer->run("
create table if not exists {$this->getTable(Bold_Checkout_Model_Synchronization::RESOURCE)}
(
    id int unsigned auto_increment comment 'Record ID' primary key,
    entity_type varchar(20) not null comment 'Entity Type',
    entity_id int unsigned not null comment 'Entity ID',
    synchronized_at timestamp null comment 'Synchronized At',
    constraint UNQ_BOLD_CHECKOUT_SYNCHRONIZATION_ENTITY_ENTITY_TYPE_ENTITY_ID unique (entity_type, entity_id)
) engine=InnoDB DEFAULT charset=utf8 comment='Sync Entities Table';

");

$installer->run("
create table if not exists {$this->getTable(Bold_Checkout_Model_Status::RESOURCE)}
(
    id int unsigned auto_increment comment 'Record ID' primary key,
    entity_type varchar(20) not null comment 'Entity Type',
    status varchar(20) not null comment 'Status',
    constraint UNQ_BOLD_CHECKOUT_SYNCHRONIZATION_STATUS_ENTITY_TYPE unique (entity_type)
) engine=InnoDB DEFAULT charset=utf8 comment='Sync Entities Status';

");

$installer->endSetup();
