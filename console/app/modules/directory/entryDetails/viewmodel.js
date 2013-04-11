define(['Boiler'], function(Boiler) {

	var ViewModel = function(moduleContext) {


        var self = this;
		//Implement the viewmodel here
        self.model = ko.observable();

        self.close = function() {
            Boiler.UrlController.goTo("directory");
        };

        self.load = function(id) {
            moduleContext.notify('Directory.get', { id: id, callback: function(data) { self.model(data); }});
        };


        self.save = function() {
            moduleContext.notify('Directory.save', { model: self.model(), callback: self.close});
        };

	};

	return ViewModel;
});
