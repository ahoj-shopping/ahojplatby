/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
var ajaxQueries = new Array();
function stopAjaxQuery() {
	if (typeof(ajaxQueries) == 'undefined')
		ajaxQueries = new Array();
	for(i = 0; i < ajaxQueries.length; i++) {
		if (typeof ajaxQueries[i] != 'undefined')
			ajaxQueries[i].abort();
	}		
	ajaxQueries = new Array();
}

$(document).ready(function() {

	var origupdatePrice = updatePrice;
	updatePrice = function(str) {
		// console.log('override updatePrice '+priceWithDiscountsDisplay);
		var response = origupdatePrice(origupdatePrice);
		showAhojPlatbyBanner();
		return response;
	}

	showAhojPlatbyBanner();
});

function showAhojPlatbyBanner()
{
	var price = 0;
	if (typeof priceWithDiscountsDisplay !== 'undefined') {
	    price = priceWithDiscountsDisplay;
	}
	else {
		price = productPrice;
	}

	$.ajax({
		type: 'POST',
		headers: { "cache-control": "no-cache" },
		url: banner_ajax_url,
		async: true,
		cache: false,
		dataType: 'json',
		data: {
			price: price
		},
		success: function(jsonData)
		{
			if(!jsonData.errors)
			{
				ahojpay.productBanner(price, '.ahojpay-product-banner', jsonData.calculations);
			}
		}
	});
}

