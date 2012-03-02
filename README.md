# Objectiveweb
Data dominates. If you've chosen the right data structures and organized things well, the algorithms will almost always be self-evident. Data structures, not algorithms, are central to programming. (Rob Pike, 1989)

# Types

# Usage

Clone the objectiveweb repository

    git clone git@github.com:bravado/objectiveweb.git

Create the configuration file ow-config.php on objectiveweb's parent directory.
A sample configuration may be found on objectiveweb/ow-config.sample.php and contains the main configuration directives for the core components.

Check your instalation on the objectiveweb/ url

    http://my-server/objectiveweb/

You should receive a JSON document with the server info. If debug is enabled, this document will also contain information about which apps are loaded.

    {
        "objectiveweb": "0.4",
        "apps": [ ]
    }

That's it for the backend installation.

