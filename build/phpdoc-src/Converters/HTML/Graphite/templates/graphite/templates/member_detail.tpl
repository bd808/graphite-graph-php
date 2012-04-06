<tr>
  <td class="right">
    {if $var.access}<em>{$var.access}</em>{/if}
    {if $var.static}static{/if}
    {if $var.var_type}<em>{$var.var_type}</em>{/if}
  </td>
  <td>
    <a name="{$var.var_name}"></a>
    <dl>
      <dt>
        <code><strong>{$var.var_name}</strong></code>
      </dt>
      <dd>{include file="docblock.tpl" sdesc=$var.sdesc desc=$var.desc tags=$var.tags}</dd>
    {if $var.var_overrides}
      <dt><strong>Overrides:</strong></dt><dd>{$var.var_overrides.link}</dd>
    {/if}
    </dl>
  </td>
</tr>

