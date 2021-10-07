{extends file='page.tpl'}

{block name="page_content_container"}
	<div>
		<center>
			<h1>{l s='PAYMENT FAIL' mod='ahojplatby'}</h1>
			{l s='After ahoj form redirect to this fail controller' mod='ahojplatby'}
		</center>

		{if $debug == 1}
		    <p>debug mode</p>
		    <div class="text-justify">
		    	
		    	<pre>{$order|print_r}</pre>

		    </div>
		{/if}
	</div>

{/block}