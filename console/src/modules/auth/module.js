define(['Boiler', './settings', './loginWindow/component'],
    function (Boiler, settings, LoginWindowComponent) {

        var Module = function (globalContext) {


            var moduleContext = new Boiler.Context(globalContext);
            moduleContext.addSettings(settings);
            var authUrl = moduleContext.getSettings().authUrl;

            /**
             * auth {}
             */
            moduleContext.listen('auth', function() {
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
                ".overlays" : new LoginWindowComponent(moduleContext)
            });
            controller.start();


        };
        return Module;
    });