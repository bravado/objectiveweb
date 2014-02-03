/*global define: true, ko: true */
define(['Boiler', 'text!./view.html', './viewmodel'], function(Boiler, template, ViewModel) {
    "use strict";

    /**
     *
     * @param nav pointer to a DataSource Navigator
     * @param params {
     *   'toolbar': -- html to be inserted at the module menu
     *   'header': -- header html
     *   'filter': {
     *     'default_field' : -- default search field
     *   },
     *   'cols': {
     *      'Label': 'field_name',
     *      'Other Label': function(model) {
     *          return model.field_name() + ' - ' + model.count()
     *      }
     *   },
     *   actions: [  -- array of buttons
     *                  {
     *                      label: 'Button label',
     *                      fn: function (model) {
     *                          -- on click
     *                      },
     *                      css: {
     *                          'btn-mini': true,
     *                          'btn-success': true
     *                      }
     *                  }
     *              ]
     *
     * }
     * @constructor
     */
	var TableView = function(nav, params) {

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

	return TableView;

});
