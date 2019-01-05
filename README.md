Language Server Extension
=========================

[![Build Status](https://travis-ci.org/phpactor/language-server-extension.svg?branch=master)](https://travis-ci.org/phpactor/language-server-extension)

The Phpactor Language Server Extension

Usage
-----

Install this extension:

```
$ phpactor extension:install phpactor/language-server-extension
```

Start a TCP server:

```
$ phpactor language-server --address=0.0.0.0:0
```

Extend
------

By default the language server doesn't provide any functionality:

```
$ phpactor extension:install phpactor/language-server-completion
$ phpactor extension:install phpactor/language-server-reference-finder
```

Now it can provide completion and goto definition, except we haven't got any
completion or goto-definition implementations, so let's fix that:

```
$ phpactor extension:install phpactor/worse-reference-finder-extension
$ phpactor extension:install phpactor/completion-worse-extension
```

Integrating with Language Clients
---------------------------------

### COC

[Conqueror of Code](https://github.com/neoclide/coc.nvim):

In `coc-settings.json` (can be accessed in VIM through `:CocConfig`:

Connect with STDIO (recommended)

```
{
    "languageserver": {
        "phpactor": {
            "command": "/home/daniel/www/phpactor/phpactor/bin/phpactor",
            "args": ["language-server"],
            "filetypes": ["php"],
            "initializationOptions": {
            },
            "settings": {
            }
        }
    }
}
```

Connect to the TCP server (will need to be started manually and settings
adjusted as required):

```
{
    "languageserver": {
        "phpactor": {
            "host": "127.0.0.1",
            "filetypes": ["php"],
            "port": 8888
        }
    }
}
```

System Commands
---------------

### Status

You can get some status information from the server by issuing:

```
:call CocRequest('phpactor','system/status', [])
```

(assuming you are using COC for VIM).

## Dump Config

Dump the configuration to the client via. `window/logMessage`:

```
:call CocRequest('phpactor','system/configDump', [])
```
