{* 
	hook payment 1.6
*}

{* <p class="payment_module">
	<a href="{$link->getModuleLink('ahojplatby', 'payment')|escape:'html'}" title="{l s='Ahoj platby' mod='ahojplatby'}">
		
		{l s='Ahoj platby' mod='ahojplatby'}&nbsp;<span>{l s='(addintional text)' mod='ahojplatby'}</span>
	</a>
</p> *}

<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
			{* TODO css podobne ako bankwire a obrazok *}
			<a class="bankwire" href="{$link->getModuleLink('ahojplatby', 'payment')|escape:'html':'UTF-8'}" title="{l s='Ahoj platby' mod='ahojplatby'}">
				{l s='Ahoj platby' mod='ahojplatby'} <span>{l s='(addintional text)' mod='ahojplatby'}</span>
			</a>
		</p>
	</div>
</div>
