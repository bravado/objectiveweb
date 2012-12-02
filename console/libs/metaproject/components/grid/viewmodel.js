define(['Boiler'], function (Boiler) {

    var ViewModel = function (nav, params) {
        var self = this;

        // TODO suportar templates como em http://jsfiddle.net/rniemeyer/VZmsy/
        self.header = params.header;

        var columns = [];

        // TODO if empty cols, montar grid a partir do model
        $.each(params.cols, function(i, e) {
            columns.push({ label: i, text: e })
        });

        this.grid = new metaproject.ui.Grid(nav, {
            columns: columns,
            actions: params.actions || []
        });


    };

    return ViewModel;
});