<?php

include_once(dirname(__FILE__) . '/../autoload.php');

class CartRuleOverride extends CartRule
{

	public function checkProductRestrictionsOverride(Context $context, $return_products = false, $display_error = true, $already_in_cart = false)
	{
		return $this->checkProductRestrictions($context, $return_products, $display_error, $already_in_cart);
	}
}
