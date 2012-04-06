{include file="header.tpl" eltype="class" hasel=true contents=$classcontents}

<a name="sec-description"></a>
<h2>{if $is_interface}Interface{else}Class{/if} {$class_name}</h2>

{if is_array($class_tree.classes) && count($class_tree.classes)}
<pre>{section name=tree loop=$class_tree.classes}{$class_tree.classes[tree]}{$class_tree.distance[tree]}{/section}</pre>
{/if}

{* ====== Interfaces ====== *}
{if $implements}
  <div>
    <a name="sec-implements"></a>
    <dl>
      <dt><strong>Implemented Interfaces:</strong></dt>
      <dd>
        {section name=impl loop=$implements}
          {$implements[impl]}{if $smarty.section.impl.last neq true},{/if}
        {/section}
      </dd>
    </dl>
  </div>
{/if}

{* ====== Descendant classes ====== *}
{if $children}
  <div>
    <a name="sec-descendants"></a>
    <dl>
      <dt><strong>Direct Known Subclasses:</strong></dt>
      <dd>
        {section name=child loop=$children}
          {$children[child].link}{if $smarty.section.child.last neq true},{/if}
        {/section}
      </dd>
    </dl>
  </div>
{/if}

{* ====== Conflicts ====== *}
{if $conflicts.conflict_type}
  <div>
    <a name="sec-conflicts"></a>
    <dl>
      <dt><span class="warning">Conflicts with classes:</span></dt>
      <dd>
        {section name=conflict loop=$conflicts.conflicts}
          {$conflicts.conflicts[conflict]}{if $smarty.section.conflict.last neq true},{/if}
        {/section}
      </dd>
    </dl>
  </div>
{/if}

<hr class="separator" />

{include file="docblock.tpl" type="class" sdesc=$sdesc desc=$desc}

{if $tutorial}
  <hr class="separator" />
  <div class="notes">Tutorial: <span class="tutorial">{$tutorial}</div>
{/if}

{if count($tags) > 0}
  <dl>
    <dt><strong>Author(s):</strong></dt>
    {section name=tag loop=$tags}
      {if $tags[tag].keyword eq "author"}
        <dd>{$tags[tag].data}</dd>
      {/if}
    {/section}
    {include file="classtags.tpl" tags=$tags}
  </dl>
{/if}

<hr class="separator" />

{* ====== Summary Tables ====== *}
{* ====== Class Constants ====== *}
{if $consts}
  <a name="sec-const-summary"></a>
  <table class="summary">
    <thead>
      <tr><th colspan="2">Constants Summary</th></tr>
    </thead>
    <tbody>
    {section name=consts loop=$consts}
      <tr>
        <td class="right"><em>public</em></td>
        <td>
          <dl>
            <dt>
              <code>
                  <a href="#const{$consts[consts].const_name}" title="details" class="const-name-summary">{$consts[consts].const_name}</a>
                </code>
            </dt>
            {if $consts[consts].sdesc}<dd>{$consts[consts].sdesc}</dd>{/if}
          </dl>
        </td>
      </tr>
    {/section}
    </tbody>
  </table>
{/if}

{* ====== Class Inherited Constants ====== *}
{if $iconsts}
  <a name="sec-inherited-consts"></a>
  <table class="summary">
    <thead>
      <tr><th>Inherited Constants</th></tr>
    </thead>
    <tbody>
    {section name=iconsts loop=$iconsts}
      {if $iconsts[iconsts] && is_array( $iconsts[iconsts] ) && count( $iconsts[iconsts] )}
            <tr>
              <td>
                {section name=iconsts2 loop=$iconsts[iconsts].iconsts}
                  {$iconsts[iconsts].iconsts[iconsts2].link}{if $smarty.section.iconsts2.last neq true},{/if}
                {/section}
              </td>
            </tr>
      {/if}
    {/section}
    </tbody>
  </table>
{/if}

{* ====== Class Members ====== *}
{if $vars}
  <a name="sec-var-summary"></a>
  <table class="summary">
    <thead>
      <tr><th colspan="2">Member Variables</th></tr>
    </thead>
    <tbody>
    {section name=vars loop=$vars}
      {if $vars[vars].static}
        {include file="member_summary.tpl" var=$vars[vars]}
      {/if}
    {/section}
    {section name=vars loop=$vars}
      {if !$vars[vars].static}
        {include file="member_summary.tpl" var=$vars[vars]}
      {/if}
    {/section}
    </tbody>
  </table>
{/if}

{* ====== Class Inherited Members ====== *}
{if $ivars}
  <table class="summary">
    <thead>
      <tr><th colspan="2">Inherited Member Variables</th></tr>
    </thead>
    {section name=ivars loop=$ivars}
    <tbody>
        <tr>
          <td>
            {section name=ivars2 loop=$ivars[ivars].ivars}
              {$ivars[ivars].ivars[ivars2].link}{if $smarty.section.ivars2.last neq true},{/if}
            {/section}
          </td>
        </tr>
    </tbody>
    {/section}
  </table>
{/if}

{* ====== Magic Properties (virtual members) ====== *}
{if $prop_tags}
  <a name="sec-prop-summary"></a>
  <table class="summary">
    <thead>
      <tr><th colspan="2">Magic Properties/Methods</th></tr>
    </thead>
    <tbody>
    {section name=props loop=$prop_tags}
      <tr>
        <td class="right">
          <a name="prop{$prop_tags[props].prop_name}" id="{$prop_tags[props].prop_name}"></a>
          {if $prop_tags[props].prop_type}<em>{$prop_tags[props].prop_type}</em>{/if}
          <em>{$prop_tags[props].access}</em>
        </td>
        <td>
          <dl>
            <dt>
              <code><strong>{$prop_tags[props].prop_name}</strong></code>
            </dt>
            {if $prop_tags[props].sdesc}<dd>{$prop_tags[props].sdesc}</dd>{/if}
          </dl>
        </td>
      </tr>
    {/section}
  </table>
{/if}


{* ====== Class Methods ====== *}
{if $methods}
  <a name="sec-method-summary"></a>
  <table class="summary">
    <thead>
      <tr><th colspan="2">Method Summary</th></tr>
    </thead>
    <tbody>
    {section name=methods loop=$methods}
      {if $method[methods].constructor}
        {include file="method_summary.tpl" method=$methods[methods]}
      {/if}
    {/section}
    {section name=methods loop=$methods}
      {if $methods[methods].static}
        {include file="method_summary.tpl" method=$methods[methods]}
      {/if}
    {/section}
    {section name=methods loop=$methods}
      {if !$methods[methods].static and !$method[methods].constructor}
        {include file="method_summary.tpl" method=$methods[methods]}
      {/if}
    {/section}
    </tbody>
  </table>
{/if}

{* ====== Inherited Methods ====== *}
{if $imethods}
  <table class="summary">
    <thead>
      <tr><th>Inherited Methods</th></tr>
    </thead>
    {section name=imethods loop=$imethods}
    <tbody>
        <tr>
          <td>
            {section name=imethods2 loop=$imethods[imethods].imethods}
              {$imethods[imethods].imethods[imethods2].link}{if $smarty.section.imethods2.last neq true},{/if}
            {/section}
          </td>
        </tr>
    </tbody>
    {/section}
  </table>
{/if}

{* ====== Detail Tables ====== *}
{if $consts}
  <a name="sec-const"></a>
  <h2 class="section-header">Constants</h2>
  <table class="detail">
    {section name=consts loop=$consts}
    <tbody>
      <tr>
        <td class="right"><em>public</em></td>
        <td>
          <a name="const{$consts[consts].const_name}"></a>
          <dl>
            <dt>
              <code><strong>{$consts[consts].const_name}</strong></code>
            </dt>
            {if $consts[consts].sdesc}<dd>{$consts[consts].sdesc}</dd>{/if}
            {if $consts[consts].desc}<dd>{$consts[consts].desc}</dd>{/if}
          </dl>
        </td>
      </tr>
    </tbody>
    {/section}
  </table>
{/if}

{* ====== Class Members ====== *}
{if $vars}
  <a name="sec-var"></a>
  <h2 class="section-header">Member Variables</h2>
  <table class="detail">
    <tbody>
    {section name=vars loop=$vars}
      {if $vars[vars].static}
        {include file="member_detail.tpl" var=$vars[vars]}
      {/if}
    {/section}
    {section name=vars loop=$vars}
      {if !$vars[vars].static}
        {include file="member_detail.tpl" var=$vars[vars]}
      {/if}
    {/section}
    </tbody>
  </table>
{/if}

{if $methods}
  <a name="sec-methods"></a>
  <h2 class="section-header">Methods</h2>
  {section name=methods loop=$methods}
    {if $method[methods].constructor}
      {include file="method_detail.tpl" method=$methods[methods]}
    {/if}
  {/section}
  {section name=methods loop=$methods}
    {if $methods[methods].static}
      {include file="method_detail.tpl" method=$methods[methods]}
    {/if}
  {/section}
  {section name=methods loop=$methods}
    {if !$methods[methods].static and !$method[methods].constructor}
      {include file="method_detail.tpl" method=$methods[methods]}
    {/if}
  {/section}
{/if}

<p class="notes">
  Located in <a class="field" href="{$page_link}">{$source_location}</a> 
  [<span class="field">line {if $class_slink}{$class_slink}{else}{$line_number}{/if}</span>]
</p>

{include file="footer.tpl"}
