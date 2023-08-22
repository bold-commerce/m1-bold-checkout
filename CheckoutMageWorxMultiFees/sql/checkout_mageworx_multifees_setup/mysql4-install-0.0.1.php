<?php
/* @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable(Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE))
    ->addColumn(
        Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::ENTITY_ID,
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ],
        'Entity ID'
    )->addColumn(
        Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID,
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'unsigned' => true,
            'nullable' => false,
        ],
        'Order ID'
    )->addColumn(
        Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::DATA,
        Varien_Db_Ddl_Table::TYPE_TEXT,
        null,
        [
            'nullable' => true,
            'default' => null,
        ],
        'Public Order ID'
    )->addIndex(
        $installer->getIdxName(
            Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE,
            [Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID]
        ),
        [Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID]
    )->addIndex(
        $installer->getIdxName(
            Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE,
            [Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID],
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        [Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
    )->addForeignKey(
        $installer->getFkName(
            Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE,
            Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID,
            'sales/quote',
            'entity_id'
        ),
        Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID,
        $installer->getTable('sales/quote'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )->setComment('Bold Checkout Quote Multi Fees Data Table');
$installer->getConnection()->createTable($table);

$installer->endSetup();
