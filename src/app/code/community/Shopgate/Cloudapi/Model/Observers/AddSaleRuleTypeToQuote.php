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

class Shopgate_Cloudapi_Model_Observers_AddSaleRuleTypeToQuote
{
    /**
     * Checks if the type exists in quote before cross referencing
     *
     * @param Varien_Event_Observer $observer
     */
    public function execute(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getData('quote');
        if ($quote->getData(Shopgate_Cloudapi_Model_SalesRule_Condition::CART_TYPE) !== null) {
            return;
        }
        $empty = Mage::getResourceModel('shopgate_cloudapi/cart_source_collection')
                     ->setQuoteFilter($quote->getId())
                     ->isEmpty();
        if (!$empty) {
            Mage::helper('shopgate_cloudapi/api2_quote')->addAppSaleRuleCondition($quote);
        }
    }
}
