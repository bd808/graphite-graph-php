<tr>
  <td class="right">
    {if $var.access}<em>{$var.access}</em>{/if}
    {if $var.static}static{/if}
    {if $var.var_type}<em>{$var.var_type}</em>{/if}
  </td>
  <td>
    <dl>
      <dt>
        <code>
          <a href="#{$var.var_name}"><strong>{$var.var_name}</strong></a>
        </code>
      </dt>
    {if $var.sdesc}<dd>{$var.sdesc}</dd>{/if}
    </dl>
  </td>
</tr>

