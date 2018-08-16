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

class Shopgate_Cloudapi_Model_Api2_Wishlists_Rest extends Shopgate_Cloudapi_Model_Api2_Resource
{
    /**
     * Validates the wishlist and that it is properly created
     *
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     *
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    protected function validateWishlist(Mage_Wishlist_Model_Wishlist $wishlist)
    {
        if (!$wishlist->getId() || $wishlist->getCustomerId() !== $this->getApiUser()->getUserId()) {
            Mage::throwException(
                Mage::helper('wishlist')->__("Requested wishlist doesn't exist")
            );
        }
    }
}
