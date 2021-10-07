{* product Additional Info *}


<script type="text/javascript">
var banner_ajax_url = "{$banner_ajax_url}";

$(document).ready(function() {

	var origupdatePrice = updatePrice;
	updatePrice = function(str) {
		// console.log('override updatePrice '+priceWithDiscountsDisplay);
		showAhojPlatbyBanner();
	    return origupdatePrice(origupdatePrice);
	}

	showAhojPlatbyBanner();
});

function showAhojPlatbyBanner()
{
	$.ajax({
		type: 'POST',
		headers: { "cache-control": "no-cache" },
		url: banner_ajax_url,
		async: true,
		cache: false,
		dataType: 'json',
		data: {
			price: priceWithDiscountsDisplay
		},
		success: function(jsonData)
		{
			if(!jsonData.errors)
			{
				ahojpay.productBanner(priceWithDiscountsDisplay, '.ahojpay-product-banner', jsonData.calculations);
			}
		}
	});
}

</script>

{if $banner_data}

	{* javascript include *}
	{$banner_data.js nofilter}

	{* banner *}
	{$banner_data.html_banner nofilter}

{/if}
