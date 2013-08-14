/*global define: true, $: true */
define(function (require) {
    "use strict";


    var Boiler = require('Boiler'),
        settings = require('./settings'),
        LoginWindowComponent = require('./loginWindow/component'),
        UserMenuComponent = require('./userMenu/component');

    return {
        initialize: function (moduleContext) {

            moduleContext.addSettings(settings);

            var authUrl = moduleContext.getSettings().authUrl;

            /**
             * auth {}
             */
            moduleContext.listen('auth', function () {
                moduleContext.notify('connecting');
                $.ajax({
                    'url': authUrl,
                    'type': 'GET',
                    'success': function (data) {
                        moduleContext.notify('connected', data);
                    },
                    'error': function () {
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
                    'type': 'POST',
                    'data': data,
                    'success': function (data) {
                        moduleContext.notify('connected', data);
                    },
                    'error': function (error) {
                        moduleContext.notify('authfail', error);
                    }
                });
            });
            /**
             * logout {}
             */
            moduleContext.listen('logout', function () {
                $.ajax({
                    url: authUrl + '/logout',
                    type: 'GET',
                    success: function (data) {
                        moduleContext.notify('disconnected');
                    }
                });
            });

            var loginWindow = new LoginWindowComponent(moduleContext),
                userMenu = new UserMenuComponent(moduleContext);


            loginWindow.activate($('body'));
            userMenu.activate($('.user-menu'));


        }
    };

});
