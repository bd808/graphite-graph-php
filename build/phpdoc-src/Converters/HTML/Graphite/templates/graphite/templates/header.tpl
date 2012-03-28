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
      </header>
      <div role="main">
        <div class="sidebar">
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
          {if !$hasel}{assign var="hasel" value=false}{/if}
          {if $eltype == 'class' && $is_interface}
            {assign var="eltype" value="interface"}
          {/if}
          {if $hasel}
            <h2>{$package}{if $subpackage != ''}::{$subpackage}{/if}::{$class_name}</h2>
          {/if}
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
