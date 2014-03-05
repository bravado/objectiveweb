/*global define: true */
define(function(require) {

    /**
     * Simple Editor for a Resource
     * @param {metaproject.Model} class
     * @constructor
     */
    var Editor = function Editor(Model) {
        var self = this;

        // Instance being edited
        self.model = ko.observable();

        self.close = function () {
            self.model(null);
        };

        self.load = function (id) {

            if (id !== undefined) {
                Model.get(id, self.model);
            }
            else {
                self.model(Model.create());
            }

        };

        self.destroy = function (vm, event) {

            if (event.currentTarget.dataset.confirm && !confirm(event.currentTarget.dataset.confirm)) {
                return;
            }

            self.model().destroy(function () {
                if (event.currentTarget.dataset.close === "true") {
                    self.close();
                }
            });

        };


        self.save = function (vm, event) {
            self.model().save(function () {
                if (event.currentTarget.dataset.close === "true") {
                    self.close();
                }
            });
        };
    };


    return Editor;

});