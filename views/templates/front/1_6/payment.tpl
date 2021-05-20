{$js_ahojpay_init nofilter}
<div>
	<center>
		<h1>{l s='GENERATING PAYMENT' mod='ahojplatby'}</h1>
		{l s='Please do not refresh or close this page' mod='ahojplatby'}
	</center>

	{if $debug == 1}
	    <p>debug mode</p>
	    url: <a href="{$response.applicationUrl}" target="_blank">{$response.applicationUrl}</a>
	    <div class="text-justify">
	    	
	    	<pre>{$response|print_r}</pre>
	    	<pre>{$data|print_r}</pre>

	    </div>
	{/if}
</div>

