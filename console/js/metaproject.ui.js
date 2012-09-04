(function(window, $, ko) {


    metaproject.ui = metaproject.ui || {};
    metaproject.ui.Grid = function(datasource, params) {
        var self = this;

        params = $.extend({}, { columns: [], actions: []}, params);
        // TODO if datasource instanceof Array ...
        self.datasource = datasource;
        self.columns = params.columns;
        self.actions = params.actions;


    };

})(window, jQuery, ko);