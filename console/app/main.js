/*global define: true, require: true */
(function () {
    "use strict"; // avoid accidental global variable declarations

    /*
     * Let's define short alias for commonly used AMD libraries and name-spaces. Using
     * these alias, we do not need to specify lengthy paths, when referring a child
     * files. We will 'import' these scripts, using the alias, later in our application.
     */
    require.config({
        paths: {
            // requirejs plugins in use
            text: '../libs/require/text',
            order: '../libs/require/order',
            i18n: '../libs/require/i18n',
            domReady: '../libs/require/domReady',
            path: '../libs/require/path',
            // objectiveweb root
            objectiveweb: '../..',
            // namespace that aggregate core classes that are in frequent use
            Boiler: './core/_boiler_'
        }
    });


    define(function (require) {

        /*
         * Let's import all dependencies as variables of this script file.
         *
         * Note: when we define the variables, we use PascalCase for namespaces ('Boiler' in the case) and classes,
         * whereas object instances ('settings' and 'modules') are represented with camelCase variable names.
         */
        var domReady = require("domReady"), // requirejs domReady plugin to know when DOM is ready
            Boiler = require("Boiler"), // BoilerplateJS namespace used to access core classes, see above for the definition
            settings = require("./settings"), //global settings file of the product suite
            modules = require("./modules/modules"); //file where all of your product modules will be listed

        //Here we use the requirejs domReady plugin to run our code, once the DOM is ready to be used.
        domReady(function () {

            /* In JavaScript, functions can be used similarly to classes in OO programming. Below,
             * we create an instance of 'Boiler.Context' by calling the 'new' operator. Then add
             * global settings. These will be propagated to child contexts
             */
            var globalContext = new Boiler.Context();
            globalContext.addSettings(settings);

            /* In BoilerplateJS, your product module hierachy is associated to a 'Context' hierarchy. Below
             * we create the global 'Context' and load child contexts (representing your product sub modules)
             * to create a 'Context' tree (product modules as a tree).
             */
            for (var i = 0; i < modules.length; i++) {
                modules[i].initialize(globalContext);
            }

        });
    });


})();
