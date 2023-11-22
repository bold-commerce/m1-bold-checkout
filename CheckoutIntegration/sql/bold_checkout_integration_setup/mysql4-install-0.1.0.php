<?php

$installer = $this;
$installer->startSetup();

if (!$installer->getConnection()->isTableExists($installer->getTable(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE))) {
    $installer->run("
        CREATE TABLE {$installer->getTable(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)} (
            entity_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
            name VARCHAR(255) NOT NULL,
            consumer_key VARCHAR(32) NOT NULL,
            secret VARCHAR(128) NOT NULL,
            callback_url TEXT NULL,
            rejected_callback_url TEXT NOT NULL,
            PRIMARY KEY (entity_id),
            UNIQUE KEY OAUTH_CONSUMER_KEY (consumer_key),
            UNIQUE KEY OAUTH_CONSUMER_SECRET (secret),
            KEY OAUTH_CONSUMER_CREATED_AT (created_at),
            KEY OAUTH_CONSUMER_UPDATED_AT (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='OAuth Consumers';
    ");
}

if (!$installer->getConnection()->isTableExists($installer->getTable(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE))) {
    $installer->run("
        CREATE TABLE {$installer->getTable(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE)} (
            entity_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            consumer_id INT(10) UNSIGNED NULL,
            type VARCHAR(16) NOT NULL,
            token VARCHAR(32) NOT NULL,
            secret VARCHAR(128) NOT NULL,
            verifier VARCHAR(32) NULL,
            callback_url TEXT NOT NULL,
            revoked SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            authorized SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            user_type INT(10) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (entity_id),
            UNIQUE KEY OAUTH_TOKEN_TOKEN (token),
            INDEX OAUTH_TOKEN_CONSUMER_ID (consumer_id),
            INDEX OAUTH_TOKEN_CREATED_AT (created_at),
            FOREIGN KEY (consumer_id) REFERENCES {$installer->getTable(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)} (entity_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='OAuth Tokens';
    ");
}

/*$installer->run("
    CREATE TABLE {$installer->getTable('bold_checkout_integration/oauth_nonce')} (
        nonce VARCHAR(32) NOT NULL,
        timestamp INT(10) UNSIGNED NOT NULL,
        consumer_id INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (nonce, consumer_id),
        INDEX OAUTH_NONCE_TIMESTAMP (timestamp),
        FOREIGN KEY (consumer_id) REFERENCES {$installer->getTable('bold_checkout_integration/oauth_consumer')} (entity_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='OAuth Nonce';
");*/

if (!$installer->getConnection()->isTableExists($installer->getTable(Bold_CheckoutIntegration_Model_Integration::RESOURCE))) {
    $installer->run("
        CREATE TABLE {$installer->getTable(Bold_CheckoutIntegration_Model_Integration::RESOURCE)} (
            integration_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            endpoint VARCHAR(255) NULL,
            status SMALLINT(5) UNSIGNED NOT NULL,
            consumer_id INT(10) UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            setup_type SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            identity_link_url VARCHAR(255) NULL,
            PRIMARY KEY (integration_id),
            UNIQUE KEY INTEGRATION_NAME (name),
            UNIQUE KEY INTEGRATION_CONSUMER_ID (consumer_id),
            FOREIGN KEY (consumer_id) REFERENCES {$installer->getTable(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)} (entity_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='integration';
    ");
}

$installer->endSetup();
