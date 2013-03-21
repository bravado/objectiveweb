define(["Boiler"], function(Boiler) {

    var ViewModel = function(moduleContext) {

        var self = this;


        self.current_user = ko.observable({});

        self.logout = function() {
            moduleContext.notify('logout');
        };


        moduleContext.listen('disconnected', function() {
            self.current_user({});
        });

        moduleContext.listen('connected', function(data) {
            self.current_user(data);
        });

    };

    return ViewModel;
});
