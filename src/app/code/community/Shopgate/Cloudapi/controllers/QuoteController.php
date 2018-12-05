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

class Shopgate_Cloudapi_QuoteController extends Mage_Core_Controller_Front_Action
{
    /**
     * Request token key
     */
    const REQUEST_TOKEN_KEY = 'token';
    /**
     * Module name of the redirect, e.g customer in customer/account/login
     */
    const REQUEST_REDIRECT_MODULE = 'module';
    /**
     * Controller name of the redirect, e.g account in customer/account/login
     */
    const REQUEST_REDIRECT_CONTROLLER = 'controller';
    /**
     * Action name of the redirect, e.g login in customer/account/login
     */
    const REQUEST_REDIRECT_ACTION = 'action';

    /**
     * Register psr-4 autoloader for cloud library
     */
    public function preDispatch()
    {
        Mage::getSingleton('shopgate_cloudapi/autoloader')->createAndRegister();
        parent::preDispatch();
    }

    /**
     * Receives the temporary token, looks up the quote of the token
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function authAction()
    {
        $token = $this->getRequest()->getParam(self::REQUEST_TOKEN_KEY);
        try {
            $quote = Mage::helper('shopgate_cloudapi/frontend_quote_token')->getQuoteByToken($token);
            $email = $quote->getCustomer()->getData('email');
            $email
                ? $this->getCheckoutHelper()->loginByEmail($email, $quote->getStore())
                : $this->getCheckoutHelper()->logoutCustomer();
            $this->getSession()->setQuoteId($quote->getId());
        } catch (Shopgate_Cloudapi_Model_Frontend_Checkout_Exception $e) {
            $this->getSession()->addError($e->getMessage());
            //@todo-sg: redirect to app / transfer messages to app
        } catch (Exception $e) {
            Mage::logException($e);
        }

        /**
         * append all parameters without token
         */
        $params = $this->getRequest()->getParams();
        unset($params[self::REQUEST_TOKEN_KEY]);
        $redirect = $this->getRedirect();


        return $this->_redirectUrl(
            Mage::helper('core/url')->addRequestParam(
                $redirect ? : Mage::helper('checkout/url')->getCheckoutUrl(),
                $params
            )
        );
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Shopgate_Cloudapi_Helper_Frontend_Checkout
     */
    protected function getCheckoutHelper()
    {
        return Mage::helper('shopgate_cloudapi/frontend_checkout');
    }

    /**
     * Retrieves the passed in redirect URL from parameters
     *
     * @return false|string
     */
    private function getRedirect()
    {
        $req    = $this->getRequest();
        $module = $req->getParam(self::REQUEST_REDIRECT_MODULE);
        if ($module) {
            $controller = $req->getParam(self::REQUEST_REDIRECT_CONTROLLER, 'index');
            $action     = $req->getParam(self::REQUEST_REDIRECT_ACTION, 'index');

            return Mage::getUrl("$module/$controller/$action", array('_secure' => true));
        }

        return false;
    }
}
