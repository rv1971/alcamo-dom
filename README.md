# Overview

This package offers extensions to the PHP DOM node classes at various levels:
* The classes in namespace `alcamo\dom` add features without adding
  properties to nodes except the document node.
* Namespace `alcamo\dom\extended`, built on top of `alcamo\dom`, adds
  properties to other nodes.
* Namespace `alcamo\dom\decorated`, built on top of
  `alcamo\dom\extended`, offers the possibility to add decorators to
  nodes.
* Namespace `alcamo\dom\psvi`, built on top of `alcamo\dom\decorated,
  adds PSVI data to elements

  
