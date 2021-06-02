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
		<div class="payment_module">
			{* TODO css podobne ako bankwire a obrazok *}
			{* <a class="ahojplatby" href="{$link->getModuleLink('ahojplatby', 'payment')|escape:'html':'UTF-8'}" title="{l s='Ahoj platby' mod='ahojplatby'}">
				{l s='Ahoj platby' mod='ahojplatby'} <span>{include file="../payment_description.tpl"}</span>
				
			</a> *}

			<div class="ahojplatby" title="{l s='Ahoj platby' mod='ahojplatby'}">
				{$payment_module_name}
				<span>{include file="../payment_description.tpl"}</span>
			</div>
		</div>

	</div>
</div>

