/*global define: true, jQuery: true, ko: true, metaproject: true */
define(['Boiler'], function (Boiler) {
    "use strict";
    var ViewModel = function (nav, params) {
        var self = this;

        // TODO support templates like http://jsfiddle.net/rniemeyer/VZmsy/
        self.header = params.header;

        if (undefined === nav.filter()._limit) {
            nav.filter.set('_limit', undefined === params.limit ? 50 : params.limit );
        }

        var columns = [];

        // TODO if empty cols, montar grid a partir do model
        jQuery.each(params.cols, function (i, e) {
            columns.push({ label: i, text: e });
        });

        self.grid = new metaproject.ui.Grid(nav, {
            columns: columns,
            actions: params.actions || []
        });

        self.paginator = {
            count: nav.observable({ _fields: 'COUNT(*)' }, function (result) {
                return result[0]['COUNT(*)']();
            }),
            filter: jQuery.extend({ default_field: 'q', fields: null }, params.filter || {}),
            page: ko.observable(1),
            rows_per_page: ko.observable(nav.filter()._limit),
            query: ko.observable(),
            next: function () {
                self.paginator.page(self.paginator.page() + 1);
            },
            prev: function () {
                self.paginator.page(self.paginator.page() - 1);
            }
        };

        self.paginator.offset = ko.computed(function () {
            var offset = (self.paginator.page() - 1) * self.paginator.rows_per_page();
            nav.filter.set('_offset', offset);
            return offset;
        }, this.paginator).extend({ throttle: 500 });

        self.paginator.query.parse = function(str) {
            var re = /([^ :]+):?([^ ]*)/g,
                q = null,
                query = {};

            while(str !== undefined && (q = re.exec(str)) !== null) {
                if(q[2].length > 0) {
                    query[q[1]] = q[2]; // TODO check if query[q[1]] already exists
                }
                else {
                    // TODO default_field may be an array [ 'OR', 'field1', 'field2' ] or object ?
                    query[self.paginator.filter.default_field] = q[1] + '%';
                }
            }

            return query;
        };

        self.paginator.query.subscribe(function (newValue) {

            var page = self.paginator.page(),
                query = self.paginator.query.parse(newValue);

            nav.filter.reset(query);

            if (page !== 1) {
                self.paginator.page(1);
            }
            else {
                self.paginator.page.valueHasMutated();
            }

            return query;
        });

        self.paginator.rows_per_page.subscribe(function (newValue) {
            nav.filter.set('_limit', newValue);
        });


        this.paginator.pages = ko.computed(function () {
            return Math.ceil(this.count() / this.rows_per_page());
        }, this.paginator);


        this.paginator.last = ko.computed(function () {
            return(this.offset() + this.rows_per_page() > this.count() ? this.count() : this.offset() + this.rows_per_page());
        }, this.paginator);
    };

    return ViewModel;
});