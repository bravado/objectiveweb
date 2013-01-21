/*global define: true, ko: true */
define(['Boiler', 'text!./view.html', './viewmodel'], function(Boiler, template, ViewModel) {
    "use strict";

	var Component = function(nav, params) {

		var vm, panel = null;

		this.activate = function(parent) {
			if (!panel) {
				vm = new ViewModel(nav, params);
				panel = new Boiler.ViewTemplate(parent, template);
				ko.applyBindings(vm, panel.getDomElement());
			}
			panel.show();
		};
		
		this.deactivate = function() {
			if(panel) {
				panel.hide();
			}
		};
	};

	return Component;

});
