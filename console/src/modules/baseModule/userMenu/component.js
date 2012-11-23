define(['require', 'Boiler', 'text!./view.html', './viewmodel'], function (require, Boiler, template, ViewModel) {

    var Component = function (moduleContext) {
        var panel = null;
        var vm = new ViewModel(moduleContext);

        return {
            activate:function (parent) {
                if (!panel) {
                    panel = new Boiler.ViewTemplate(parent, template);
                    ko.applyBindings(vm, panel.getDomElement());
                }
                panel.show();
            },
            deactivate : function() {
                if (panel) {
                    //panel.hide();
                }
            }
        }
    };

    return Component;
});