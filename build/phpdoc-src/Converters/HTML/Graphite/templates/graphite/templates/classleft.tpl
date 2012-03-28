{foreach key=subpackage item=files from=$classleftindex}
  <div class="package"><ul>
	{if $subpackage != ""}<li>{$subpackage}<ul>{/if}
	{section name=files loop=$files}
		{if $files[files].link != ''}<li><a href="{$files[files].link}">{/if}{$files[files].title}{if $files[files].link != ''}</a></li>{/if}
	{/section}
	{if $subpackage != ""}</ul></li>{/if}
  </ul>
  </div>
{/foreach}
<!--
{foreach key=subpackage item=files from=$classleftindex}
  <ul>
	{if $subpackage != ""}<li>{$subpackage}{/if}
	{section name=files loop=$files}
    {if $subpackage != ""}<ul>{/if}
		{if $files[files].link != ''}<li><a href="{$files[files].link}">{/if}{$files[files].title}{if $files[files].link != ''}</a></li>{/if}
    {if $subpackage != ""}</ul></li>{/if}
	{/section}
  </ul>
{/foreach}
-->
