<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <title>{$title}</title>
    <link rel="stylesheet" type="text/css" href="{$subdir}media/style.css" />
  </head>
  <body>
    <div id="container">
      <header id="header">
        <h1>{$maintitle}{if $title != $maintitle} :: {$title}{/if}</h1>
        <a href="https://github.com/bd808/graphite-graph-php"><img style="position:absolute;top:0;right:0;border:0;" src="https://a248.e.akamai.net/assets.github.com/img/7afbc8b248c68eb468279e8c17986ad46549fb71/687474703a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub"></a>
      </header>
      <div id="main">
        <div id="sidebar">
          {if count($ric) >= 1}
            <div class="package">
              <div id="ric">
                {section name=ric loop=$ric}
                  <p><a href="{$subdir}{$ric[ric].file}">{$ric[ric].name}</a></p>
                {/section}
              </div>
            </div>
          {/if}
          {if $tutorials}
            <h2>Tutorials/Manuals:</h2>
            <div class="package">
              {if $tutorials.pkg}
                <strong>Package-level:</strong>
                {section name=ext loop=$tutorials.pkg}
                  {$tutorials.pkg[ext]}
                {/section}
              {/if}
              {if $tutorials.cls}
                <strong>Class-level:</strong>
                {section name=ext loop=$tutorials.cls}
                  {$tutorials.cls[ext]}
                {/section}
              {/if}
              {if $tutorials.proc}
                <strong>Procedural-level:</strong>
                {section name=ext loop=$tutorials.proc}
                  {$tutorials.proc[ext]}
                {/section}
              {/if}
            </div>
          {/if}
          <h2>Packages:</h2>
          <div class="package">
            <ul>
              {section name=packagelist loop=$packageindex}
                <li>
                  <a href="{$subdir}{$packageindex[packagelist].link}">{$packageindex[packagelist].title}</a>
                </li>
              {/section}
            </ul>
          </div>
          {if !$noleftindex}{assign var="noleftindex" value=false}{/if}
          {if !$noleftindex}
            {if $compiledinterfaceindex}
              <h2>Interfaces:</h2>
              {eval var=$compiledinterfaceindex}
            {/if}
            {if $compiledclassindex}
              <h2>Classes:</h2>
              {eval var=$compiledclassindex}
            {/if}
          {/if}
          {if $hastodos}
            <div class="package">
              <div id="todolist">
                <p><a href="{$subdir}{$todolink}">Todo List</a></p>
              </div>
            </div>
          {/if}
        </div>
        <div id="content">
          <div class="nav">
            {assign var="packagehaselements" value=false}
            {foreach from=$packageindex item=thispackage}
              {if in_array($package, $thispackage)}
                {assign var="packagehaselements" value=true}
              {/if}
            {/foreach}
            [ <a href="{$subdir}index.html">Index</a> ]
            {if $packagehaselements}
              [ <a href="{$subdir}classtrees_{$package}.html">{$package} classes</a> ]
              [ <a href="{$subdir}elementindex_{$package}.html">{$package} elements</a> ]
            {/if}
            [ <a href="{$subdir}elementindex.html">All elements</a> ]
          </div>
