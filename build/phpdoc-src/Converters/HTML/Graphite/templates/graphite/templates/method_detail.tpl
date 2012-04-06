<div class="method-detail">
  <a name="method{$method.function_name}" id="{$method.function_name}"><!-- --></a>
  <h3>{$method.function_name}</h3>

  <div class="method-signature">
    {if $method.abstract}<em>{$method.abstract}</em>{/if}
    {if $method.access}{$method.access}{/if}
    {if $method.static}static{/if}
    {$method.function_return}
    {if $method.ifunction_call.returnsref}&amp;{/if}<strong>{$method.function_name}</strong>(
      {if count($method.ifunction_call.params)}
        {section name=params loop=$method.ifunction_call.params}
          {if $smarty.section.params.iteration != 1}, {/if}
          {if $method.ifunction_call.params[params].hasdefault}[{/if}{$method.ifunction_call.params[params].type}
          {$method.ifunction_call.params[params].name}
          {if $method.ifunction_call.params[params].hasdefault} = {$method.ifunction_call.params[params].default}]{/if}
        {/section}
      {/if})
  </code>
  </div>

  <dl>
    <dd>{include file="docblock.tpl" sdesc=$method.sdesc desc=$method.desc}</dd>
  </dl>

  <dl class="method-attributes">
  {if $method.method_implements}
    <dt>Specified by:</dt>
    {section name=imp loop=$method.method_implements}
    <dd>
      <code>{$method.method_implements[imp].link}</code>
    </dd>
    {/section}
  {/if}

  {if $method.method_overrides}
    <dt>Overrides:</dt>
    <dd><code>{$method.method_overrides.link}</code></dd>
  {/if}

  {if $method.params}
    <dt>Parameters:</dt>
    {section name=params loop=$method.params}
    <dd>
      <em>{$method.params[params].datatype}</em>
      <code>{$method.params[params].var}</code>
        {if $method.params[params].data}
          - {$method.params[params].data}
        {/if}
    </dd>
    {/section}
  {/if}

  {if $method.exceptions}
    <dt>Exceptions:</dt>
    {section name=exception loop=$method.exceptions}
    <dd>
      <code>{$method.exceptions[exception].type}</code>
      {if $method.exceptions[exception].data}
        - {$method.exceptions[exception].data}
      {/if}
    </dd>
    {/section}
  {/if}

  {if count($method.api_tags) > 0}
    {assign var="last_keyword" value=""}
    {section name=tag loop=$method.api_tags}
      {if $method.api_tags[tag].keyword ne "access"}
        {if $method.api_tags[tag].keyword ne $last_keyword}
          <dt>{$method.api_tags[tag].keyword|capitalize}:</dt>
        {/if}
        {assign var="last_keyword" value=$method.api_tags[tag].keyword}
        <dd>{$method.api_tags[tag].data}</dd>
      {/if}
    {/section}
  {/if}

  </dl>
</div>
