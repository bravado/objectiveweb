/*global define: true, $: true */
define(['Boiler', 'text!./view.html'], function (Boiler, template) {

    "use strict";

    var Component = function (moduleContext) {
        var panel = null;

        moduleContext.listen('connected', function (jid) {
            $('#login-window button').button('reset');
            $('#login-form .password').val('');
            $('#login-window').modal('hide');
        });

        moduleContext.listen('disconnected', function () {
            $('#login-window').modal('show');
        });

        moduleContext.listen('authfail', function (error) {
            $('#login-window button').button('reset');
            $('#login-window .authfail').text((error || {}).responseText).show();
            $('#login-form .password').select().focus();
            $("#login-window").effect( "shake", {}, "fast" );
        });

        return {
            activate:function (parent) {
                if (!panel) {
                    // Create the view
                    panel = new Boiler.ViewTemplate(parent, template);


                    // Set the form submit action
                    $('#login-window form').on('submit', function () {

                        $('#login-window .alert').hide();
                        $('#login-window button').button('loading');

                        moduleContext.notify('login', {
                            identifier: $('#login-form .identifier').val(),
                            password: $('#login-form .password').val()
                        });

                        return false;
                    });


                    moduleContext.notify('auth');

                }
                panel.show();
                $('#login-form .identifier').focus();
            },
            deactivate:function () {

            }
        };
    };

    return Component;
});