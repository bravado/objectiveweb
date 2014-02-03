/*global define: true, require: true, $: true, hasher: true */
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
            text: '../../objectiveweb/console/libs/require/text',
            order: '../../objectiveweb/console/libs/require/order',
            i18n: '../../objectiveweb/console/libs/require/i18n',
            domReady: '../../objectiveweb/console/libs/require/domReady',
            path: '../../objectiveweb/console/libs/require/path',
            objectiveweb: '../../objectiveweb',
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

            // Modules that will be loaded
            modules = [
                // require('./modules/moduleName/module')
            ];


        //Here we use the requirejs domReady plugin to run our code, once the DOM is ready to be used.
        domReady(function () {

            function mainmenu_update() {
                var hash = window.location.hash;
                if(hash === '') {
                    hash = '#/';
                }

                var $link = $('.main-menu a[href="' + hash + '"]');
                if($link.length === 0) {
                    $link = $('.main-menu a[href="#/"]');
                }

                $('.main-menu li.active').removeClass('active');

                $link.parents('li').addClass('active');


            }


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

                var context = new Boiler.Context(globalContext),
                    routes = modules[i].initialize(context);


                if(undefined !== routes) {
                    var controller = new Boiler.UrlController($(".appcontent"));

                    controller.addRoutes(routes);
                    controller.start();
                }
            }

            $(document).ajaxError(function(event, jqXHR, settings) {
                if(jqXHR.status !== 401) {
                    alert(jqXHR.responseText);
                }
            });

            // Atualizar o menu principal quando o hash muda
            hasher.changed.add(mainmenu_update);

            mainmenu_update();

        });
    });
}());