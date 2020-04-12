Language Server Extension
=========================

[![Build Status](https://travis-ci.org/phpactor/language-server-extension.svg?branch=master)](https://travis-ci.org/phpactor/language-server-extension)

The Phpactor Language Server Extensions.

This macro package implements all Phpactor language server functionality based
on the generic [LanguageServer](https://github.com/phpactor/language-server)
package.

Usage
-----

This package is bundled with Phpactor.

Structure
---------

Each extension should be treated as a separate package, coupling only to what
is necessary. In the future, if it were desired, it should be possible to
break the packages out of this macro repository.

Documentation
-------------

For more information on the Phpactor Language Server implementation see the
[Phpactor LSP documentation](https://phpactor.github.io/phpactor/lsp.html).
