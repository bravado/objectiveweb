metaproject
===========

Work in progress... development used to happen on the objectiveweb tree.

Now that the build system is implemented, other projects should use the minimized file.

* TODO
	* Remove underscore dependency on metaproject.js
    * Documentation and Examples (Compendium)
        * Application and Loader
        * DataSource
        * Model
        * Data-binding (knockoutjs)
        * Fileupload
        * Mask input
        * Tinymce
    * Components (for large scale apps, won't work without requirejs)
    	* Grid - make the filter configurable (default fields, advanced filter pane)
    		* Maybe rename this component, it's more than just a grid

* Far future
    * requirejs module
    * use bower or some other package management system



Main libraries
--------------
http://twitter.github.com/bootstrap/ - Bootstrap is Twitter's toolkit for kickstarting CSS for websites, apps, and more. It includes base CSS styles for typography, forms, buttons, tables, grids, navigation, alerts, and more.

http://jqueryui.com/ - jQuery UI is the official jQuery user interface library. It provides interactions, widgets, effects, and theming for creating Rich Internet Applications.

http://knockoutjs.com/ - Simplify dynamic JavaScript UIs by applying the Model-View-View Model (MVVM) pattern.

Additional Libraries
--------------------

These mostly maintain backwards-compatibility with older browsers

http://www.modernizr.com - Modernizr is an open-source JavaScript library that helps you build the next generation of HTML5 and CSS3-powered websites.

json2.js from https://github.com/douglascrockford/JSON-js - This file creates a JSON property in the global object, if there
isn't already one, setting its value to an object containing a stringify
method and a parse method. The parse method uses the eval method to do the
parsing, guarding it with several regular expressions to defend against
accidental code execution hazards. On current browsers, this file does nothing,
prefering the built-in JSON object.

There are also some snippets from the excellent http://html5boilerplate.com/

Usage
-----

All library and css dependencies are bundled on the build root. The
starter template showcases the basic usage.

The Compendium holds documentation and examples for the ui components and
general application structure.

You can use Twitter Bootstrap in one of two ways: just drop the compiled CSS
into any new project and start cranking, or run LESS on your site and compile
on the fly like a boss.

Here's what the LESS version looks like:

``` html
<link rel="stylesheet/less" type="text/css" href="lib/bootstrap.less">
<script src="less.js" type="text/javascript"></script>
```

Or if you prefer, the standard css way:

``` html
<link rel="stylesheet" type="text/css" href="bootstrap.css">
```

For more info, refer to the docs!


Versioning
----------

For transparency and insight into our release cycle, and for striving to maintain backwards compatibility, Bootstrap will be maintained under the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the follow format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backwards compatibility bumps the major
* New additions without breaking backwards compatibility bumps the minor
* Bug fixes and misc changes bump the patch

For more information on SemVer, please visit http://semver.org/.


Bug tracker
-----------

Have a bug? Please create an issue here on GitHub!

https://github.com/bravado/metaproject/issues

Developers
----------

We have included a makefile with convenience methods for working with the bootstrap library.

+ **build** - `make build`
This will run the less compiler on the bootstrap lib and generate a bootstrap.css and bootstrap.min.css file.
The lessc compiler is required for this command to run.

+ **watch** - `make watch`
This is a convenience method for watching your less files and automatically building them whenever you save.
Watchr is required for this command to run.


Authors
-------

**Guilherme Barile**

+ http://github.com/guigouz



License
---------------------

Copyright 2011 (c) Bravado

Licensed under the MIT License, other components licenses are listed on the LICENSES file.