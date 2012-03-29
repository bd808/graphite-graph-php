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
