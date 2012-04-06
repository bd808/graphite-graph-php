<tr>
  <td class="right">
    {if $method.access}<em>{$method.access}</em>{/if}
    {if $method.static}static{/if}
    {if $method.abstract}abstract{/if}
    {if $method.function_return}<em>{$method.function_return}</em>{/if}
  </td>
  <td>
    <dl>
      <dt>
        <code>
          <a href="#{$method.function_name}"><b>{if $method.ifunction_call.returnsref}&amp;{/if}{$method.function_name}</b></a>(
          {if count($method.ifunction_call.params)}
            {section name=params loop=$method.ifunction_call.params}
              {if $smarty.section.params.iteration != 1}, {/if}
              {if $method.ifunction_call.params[params].default != ''}[{/if}
              {$method.ifunction_call.params[params].name}
              {if $method.ifunction_call.params[params].default != ''} = {$method.ifunction_call.params[params].default}]{/if}
            {/section}
          {/if} )
        </code>
      </dt>
    {if $method.sdesc}<dd>{$method.sdesc}</dd>{/if}
    </dl>
  </td>
</tr>
