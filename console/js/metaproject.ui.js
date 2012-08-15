(function(window, $, ko) {
    metaproject.ui = {};

    metaproject.ui.Grid = function(data) {
        var self = this;

        if(data instanceof Array) {
            self.data = ko.observableArray(data);
        }
        else {
            self.data = ko.computed // TODO
        }
    };
})(window, jQuery, ko);