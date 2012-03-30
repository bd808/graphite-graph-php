{if count($toc)}
<div class="toc">
  <ul>
  {assign var="last_context" value="refsect1"}
  {section name=toc loop=$toc}
    {if $toc[toc].tagname == 'refsect1'}
      {assign var="context" value="refsect1"}
      {if $last_context == 'refsect2'}</ul>{/if}
      {if $last_context == 'refsect3'}</ul></ul>{/if}
      {assign var="last_context" value="refsect1"}
      <li>{$toc[toc].link}
    {/if}
    {if $toc[toc].tagname == 'refsect2'}
      {assign var="context" value="refsect2"}
      {if $last_context == 'refsect1'}<ul>{/if}
      {if $last_context == 'refsect3'}</ul>{/if}
      {assign var="last_context" value="refsect2"}
      <li>{$toc[toc].link}
    {/if}
    {if $toc[toc].tagname == 'refsect3'}
      {assign var="context" value="refsect3"}
      {if $last_context == 'refsect2'}<ul>{/if}
      {if $last_context == 'refsect1'}<ul><ul>{/if}
      {assign var="last_context" value="refsect3"}
      <li>{$toc[toc].link}
    {/if}
    {if $toc[toc].tagname == 'table'}
      <li>Table: {$toc[toc].link}
    {/if}
    {if $toc[toc].tagname == 'example'}
      <li>Table: {$toc[toc].link}
    {/if}
  {/section}
</ul>
</div>
{/if}
