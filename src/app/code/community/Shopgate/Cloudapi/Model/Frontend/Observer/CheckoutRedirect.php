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

class Shopgate_Cloudapi_Model_Frontend_Observer_CheckoutRedirect
{
    /**
     * Redirects the customer to checkout if this is a call from our App.
     * Saves the messages in our custom session to pass along to the checkout page.
     */
    public function execute()
    {
        if (!Mage::helper('shopgate_cloudapi/request')->isShopgateCheckout()) {
            return;
        }

        $messages = Mage::getSingleton('checkout/session')->getMessages(true)->getItems();
        Mage::getSingleton('shopgate_cloudapi/storage_session')->addMessages($messages);
        Mage::app()->getResponse()->setRedirect(Mage::helper('checkout/url')->getCheckoutUrl());
    }
}
