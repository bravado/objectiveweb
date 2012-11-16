define(['Boiler'], function (Boiler) {

    var ViewModel = function (nav, params) {
        var self = this;

        // Datasource
        self.data = nav;

        // TODO montar grid a partir do model

        var columns = [];
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