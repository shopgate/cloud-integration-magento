<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/** @var Shopgate_Cloudapi_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $conn */
/** @var Shopgate_Cloudapi_Model_OAuth2_Db_Pdo $pdo */
$pdo = Mage::getModel('shopgate_cloudapi/oAuth2_db_pdo', array($conn->getConnection()));
$installer->run($pdo->getBuildSql()); // installs new resource table
/** @noinspection PhpUnhandledExceptionInspection */
$table = $installer->getConnection()
                   ->newTable('shopgate_order_sources')
                   ->addColumn(
                       'entity_id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                           'identity' => true,
                           'unsigned' => true,
                           'nullable' => false,
                           'primary'  => true
                       )
                   )
                   ->addColumn(
                       'order_id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                           'unsigned' => true,
                           'nullable' => false
                       )
                   )
                   ->addColumn(
                       'source',
                       Varien_Db_Ddl_Table::TYPE_VARCHAR,
                       100,
                       array(
                           'unsigned' => true,
                           'nullable' => false
                       )
                   )
                   ->addForeignKey(
                       $installer->getFkName('shopgate_order_sources', 'order_id', 'sales/order', 'entity_id'),
                       'order_id',
                       $installer->getTable('sales/order'),
                       'entity_id',
                       Varien_Db_Ddl_Table::ACTION_CASCADE
                   )
                   ->setComment('Shopgate Order Sources');

try {
    $installer->getConnection()->createTable($table);
    if ($installer->getConnection()->isTableExists('shopgate_resource_auth_codes')) {
        $installer->getConnection()->query(
            'INSERT INTO `shopgate_oauth_authorization_codes` (`authorization_code`, `client_id`, `user_id`, `expires`, `resource_id`, `resource_type`) SELECT `token`, 0, `user_id`, `expires`, `resource_id`, `type` FROM `shopgate_resource_auth_codes`;'
        );
        $installer->getConnection()->dropTable( 'shopgate_resource_auth_codes' );
    }
} catch (Exception $e) {
    Mage::logException($e);
}
$installer->endSetup();
