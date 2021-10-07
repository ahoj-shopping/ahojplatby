{* payment description *}

{if $description}


	<div class="row clearfix">

		<div class="ahojplatby_description ">
			
			{* javascript include *}
			{$description.js nofilter}

			{* banner *}
			{$description.html_description nofilter}

		</div>
		<img class="ahojplatby_logo" src="{$ahoj_logo_url}" alt="" width="55" height="31">


	</div>


{/if}
