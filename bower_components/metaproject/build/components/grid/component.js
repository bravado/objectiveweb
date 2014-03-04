/*global define: true, ko: true */
define(['Boiler', 'text!./view.html', './viewmodel'], function(Boiler, template, ViewModel) {
    "use strict";

	var Component = function(nav, params) {

		var vm, panel = null, menuWidget = null;




		this.activate = function(parent) {
			if (!panel) {

				vm = new ViewModel(nav, params);
				panel = new Boiler.ViewTemplate(parent, template);
                if(params.toolbar) {
                    menuWidget = new Boiler.ViewTemplate($('.module-menu'), params.toolbar);
                }

                ko.applyBindings(vm, panel.getDomElement());
			}
			panel.show();
            if(menuWidget) {
                menuWidget.show();
            }

		};
		
		this.deactivate = function() {
			if(panel) {
				panel.hide();
                if(menuWidget) {
                    menuWidget.hide();
                }

			}
		};
	};

	return Component;

});
