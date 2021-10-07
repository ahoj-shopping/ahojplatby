{* 
	hook payment 1.6
*}

{* <p class="payment_module">
	<a href="{$link->getModuleLink('ahojplatby', 'payment')|escape:'html'}" title="{l s='Ahoj platby' mod='ahojplatby'}">
		
		{l s='Ahoj platby' mod='ahojplatby'}&nbsp;<span>{l s='(addintional text)' mod='ahojplatby'}</span>
	</a>
</p> *}
{if count($available_payment_methods) && $available_payment_methods}
	{foreach $available_payment_methods as $payment_method}
		<div class="row">
			<div class="col-xs-12">
				<div class="payment_module">

					<div class="ahojplatby" data-href="{$payment_method.action|escape:'html':'UTF-8'}" title="{$payment_method.name}">
						<span>{$payment_method.name}</span>
					
						<img class="ahojplatby_logo_right" src="{$ahoj_logo_url}" alt="">
						
						<span>
							<span class="row clearfix">

								<span class="ahojplatby_description">
									
									{* javascript include *}
									{$payment_method.description.js nofilter}

									{* banner *}
									{$payment_method.description.html_description nofilter}

								</span>

							</span>
						</span>

					</div>
				</div>

			</div>
		</div>
	{/foreach}

	<script type="text/javascript">
		$(document).on('click', '.payment_module .ahojplatby', function(e) {
			if($(this).data('href'))
			{
			    window.location = $(this).data('href');
			}
		});
		$(document).on('click', '.payment_module .ahojplatby a', function(e) {
			e.stopPropagation();
		});
	</script>
{/if}


