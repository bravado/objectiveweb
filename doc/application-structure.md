

# Application files

## _init.php

The application initialization file is included automatically by the framework and should define the domains, plugins and other code dependencies.

### Domains

Domains are defined using the register_domain(id, params) directive. The only required parameter is which class should handle this domain's requests, other configuration parameters are handler-specific.

    register_domain('id', array(
        "handler" => "OW_HandlerImpl"
    );

Objectiveweb includes the TableStore and ObjectStore handlers by default, additional handlers can be implemented by extending the OW_Handler class.
