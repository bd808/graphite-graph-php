{* ==expects to be wrapped in a dl== *}
{if count($info_tags) > 0}
  {assign var="last_keyword" value=""}
  {section name=tag loop=$info_tags}
    {if $info_tags[tag].keyword ne "author"}
      {if $info_tags[tag].keyword ne $last_keyword}
        <dt><strong>{$info_tags[tag].keyword|capitalize}:</strong></dt>
      {/if}
      {assign var="last_keyword" value=$info_tags[tag].keyword}
      <dd>{$info_tags[tag].data}</dd>
    {/if}
  {/section}
{/if}
