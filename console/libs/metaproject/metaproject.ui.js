(function(window, $, ko) {

    metaproject.ui = metaproject.ui || {};
    metaproject.ui.Grid = function(data, params) {
        var self = this;

        params = $.extend({}, { columns: [], actions: []}, params);
        // TODO if datasource instanceof Array ...
        self.data = data;
        self.columns = params.columns;
        self.actions = params.actions;


    };


})(window, jQuery, ko);