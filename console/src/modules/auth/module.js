/*global define: true, $: true */
define(['Boiler', './settings', './loginWindow/component', './userMenu/component'],
    function (Boiler, settings, LoginWindowComponent, UserMenuComponent) {
        "use strict";

        var Module = function (globalContext) {


            var moduleContext = new Boiler.Context(globalContext);
            moduleContext.addSettings(settings);
            var authUrl = moduleContext.getSettings().authUrl;

            /**
             * auth {}
             */
            moduleContext.listen('auth', function() {
                moduleContext.notify('connecting');
                $.ajax({
                    'url': authUrl,
                    'type':'GET',
                    'success':function (data) {
                        moduleContext.notify('connected', data);
                    },
                    'error': function() {
                        moduleContext.notify('disconnected');
                    }
                });
            });

            /**
             * login {
             *  identifier: User ID
             *  password: Password
             * }
             */
            moduleContext.listen('login', function (data) {

                moduleContext.notify('connecting');

                $.ajax({
                    'url': authUrl,
                    'type':'POST',
                    'data': data,
                    'success':function (data) {
                        moduleContext.notify('connected', data);
                    },
                    'error':function (error) {
                        moduleContext.notify('authfail', error);
                    }
                });
            });

            /**
             * logout {}
             */
            moduleContext.listen('logout', function() {
                $.ajax({
                    url: authUrl + '/logout',
                    type:'GET',
                    success:function (data) {
                        moduleContext.notify('disconnected');
                    }
                });
            });

            //scoped DomController that will be effective only on $('body')
            var controller = new Boiler.DomController($('body'));
            //add routes with DOM node selector queries and relevant components
            controller.addRoutes({
                ".overlays" : new LoginWindowComponent(moduleContext),
                ".user-menu": new UserMenuComponent(moduleContext)
            });
            controller.start();


        };
        return Module;
    });