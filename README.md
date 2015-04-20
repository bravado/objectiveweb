Objectiveweb
============

Data dominates. If you've chosen the right data structures and
organized things well, the algorithms will almost always be
self-evident. Data structures, not algorithms, are central to
programming. (Rob Pike, 1989)

## Getting ready for release

Objectiveweb 0.10 will be the first stable release of the framework.

### TODO

 * 0.10 release
	* Package the libs properly (to be used with composer) [OK]
		* Use some OO stuff (ie namespaces) [OK]
	* Integrate phpgacl into the Directory [OK]
	* Implement/Test the Attachments service
	* Implement/Test the Acl service
	* Finish implementing elfinder-based attachments

 * Post-0.10 release
	* Port TableStore and ObjectStore to the SQLStore using PDO (objectiveweb/db) 
	instead of mysqli for the backend

# Usage

Clone the Objectiveweb repository

    git clone git://github.com/bravado/objectiveweb.git

Create the configuration file ow-config.php on Objectiveweb's parent directory.
A sample configuration may be found on objectiveweb/ow-config.sample.php and contains the main configuration directives for the core components.

Check your instalation on the objectiveweb/ url

    http://my-server/objectiveweb/

You should receive a JSON document with the server info. 
If debug is enabled, this document will also contain information about which domains are loaded.

    {
        "objectiveweb": "0.10",
        "domains": [ ]
    }

That's it for the backend installation.

# Application Deployment

Applications are stored as directories on the same root as your objectiveweb installation.
This allows sharing configuration directives and libraries on a tidy directory structure.

The layout for a basic Objectiveweb project looks like

    [Project Root]
        |- application/
        |   `- _init.php
        |- objectiveweb/
        `- ow-config.php

## Registering your application

Add your application to the core adding the following directive to ow-config.php

    register_app('application');

This registers an application stored on the "application" directory.

A sample app named "skeleton" is available at [https://github.com/objectiveweb/skeleton](https://github.com/objectiveweb/skeleton)

License
-------

Copyright 2011 (c) Bravado

Licensed under the MIT License, other components licenses are listed on the LICENSES file.