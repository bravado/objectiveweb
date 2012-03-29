# Objectiveweb
Data dominates. If you've chosen the right data structures and organized things well, the algorithms will almost always be self-evident. Data structures, not algorithms, are central to programming. (Rob Pike, 1989)

# Usage

Clone the Objectiveweb repository

    git clone git://github.com/bravado/objectiveweb.git

Create the configuration file ow-config.php on Objectiveweb's parent directory.
A sample configuration may be found on objectiveweb/ow-config.sample.php and contains the main configuration directives for the core components.

Check your instalation on the objectiveweb/ url

    http://my-server/objectiveweb/

You should receive a JSON document with the server info. If debug is enabled, this document will also contain information about which apps are loaded.

    {
        "objectiveweb": "0.4",
        "apps": [ ]
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

A sample app named "skeleton" is available at [https://github.com/bravado/skeleton](https://github.com/bravado/skeleton)
