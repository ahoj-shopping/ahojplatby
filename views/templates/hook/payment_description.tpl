{* payment description *}

{if $description}

	{* javascript include *}
	{$description.js nofilter}

	{* banner *}
	{$description.html_description nofilter}

{/if}
