Graphite_GraphBuilder
=====================

[Graphite_GraphBuilder][] is a DSL and ini-based templating language to assist
in constructing query strings for use with [Graphite][].

[![Build Status][ci-status]][ci-home]

About
-----

[Graphite][] provides several interfaces for creating graphs and dashboards,
but one of its powerful features is an [API][url-api] for generating graphs
and retrieving raw data. This allows easy embedding of graphs in custom
dashboards and other applications.

The process of describing complex graphs is however cumbersome at best.
Graphite_GraphBuilder attempts to reduce the complexity of embedding
Graphite graphs in [PHP][] based applications by providing a fluent API for
describing graphs and a facility for loading full or partial graph
descriptions from ini files.


Examples
--------
For usage and installation instructions see the main [Graphite_GraphBuilder][]
site.


Credits
-------
Written by [Bryan Davis][bd808] with support from [Keynetics][].

Inspired by https://github.com/ripienaar/graphite-graph-dsl/

---
[Graphite_GraphBuilder]: https://bd808.com/graphite-graph-php/
[Graphite]: http://graphite.wikidot.com/
[url-api]: http://readthedocs.org/docs/graphite/en/latest/url-api.html
[PHP]: http://php.net/
[ci-status]: https://secure.travis-ci.org/bd808/graphite-graph-php.png
[ci-home]: http://travis-ci.org/bd808/graphite-graph-php
[bd808]: http://bd808.github.com/
[Keynetics]: http://keynetics.com/
