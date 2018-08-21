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

class Shopgate_Cloudapi_Model_Api2_Wishlists_Items_Rest extends Shopgate_Cloudapi_Model_Api2_Wishlists_Rest
{
    /**
     * @param Mage_Wishlist_Model_Item $wishlistItem
     *
     * @throws Mage_Api2_Exception
     */
    protected function validateWishListItem(Mage_Wishlist_Model_Item $wishlistItem)
    {
        if ($wishlistItem->getData('has_error')) {
            $this->_critical(
                $wishlistItem->getData('message'),
                Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR
            );
        }
        if (!$wishlistItem->getId() || $wishlistItem->isDeleted()) {
            $this->_critical(
                Mage::helper('wishlist')->__('An error occurred while adding item to wishlist.'),
                Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR
            );
        }
    }
}
