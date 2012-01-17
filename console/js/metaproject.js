// usage: log('inside coolFunc', this, arguments);
// paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function () {
    log.history = log.history || [];   // store logs to an array for reference
    log.history.push(arguments);
    if (this.console) {
        arguments.callee = arguments.callee.caller;
        var newarr = [].slice.call(arguments);
        (typeof console.log === 'object' ? log.apply.call(console.log, console, newarr) : console.log.apply(console, newarr));
    }
};

// make it safe to use console.log always
(function (b) {
    function c() {
    }

    for (var d = "assert,clear,count,debug,dir,dirxml,error,exception,firebug,group,groupCollapsed,groupEnd,info,log,memoryProfile,memoryProfileEnd,profile,profileEnd,table,time,timeEnd,timeStamp,trace,warn".split(","), a; a = d.pop();) {
        b[a] = b[a] || c
    }
})((function () {
    try {
        console.log();
        return window.console;
    } catch (err) {
        return window.console = {};
    }
})());

// JSON implementation for browsers that do not support it
Modernizr.load({
    test:window.JSON,
    nope:'json2.js'
});

ko.bindingHandlers.icon = {
    init:function (element, valueAccessor) {

        var icon = '<span class="ui-icon ui-icon-' + valueAccessor() + '"></span>';

        jQuery(element).prepend(icon);
    }
};

ko.bindingHandlers.include = {
    init:function (element, valueAccessor) {
        jQuery(element).include(valueAccessor());
    }
};

ko.bindingHandlers.autocomplete = {
    init:function (element, valueAccessor, allBindingsAccessor, viewModel) {

        var $element = jQuery(element),
            params = valueAccessor();

        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
            $element.autocomplete("destroy");
        });

        // treat String, callback or Array as source
        if(typeof(params) == 'string' || typeof(params) == 'function' || params instanceof Array) {
            params = { source: params };
        }

        var $autocomplete = $element.autocomplete(params).data('autocomplete');

        // Custom render callback http://jqueryui.com/demos/autocomplete/#custom-data
        // TODO render as string => ko templates ?
        if(undefined != params.renderItem) {
            $autocomplete._renderItem = params.renderItem;
        }

        if(undefined != params.renderMenu) {
            $autocomplete._renderMenu = params.renderMenu;
        }
    }
};

ko.bindingHandlers.datepicker = {
    init: function(element, valueAccessor, allBindingsAccessor) {

        var $element = jQuery(element);

        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
            $element.datepicker("destroy");
        });

        //initialize datepicker with some optional options
        var options = allBindingsAccessor().datepickerOptions || {};
        $element.datepicker(options);

        //handle the field changing
        // TODO verificar se value é Date ou String e configurar de acordo
        ko.utils.registerEventHandler(element, "change", function () {
            var observable = valueAccessor();
            var date = $.datepicker.formatDate('yy-mm-dd', $(element).datepicker("getDate"));

            console.log("setting date " + date);
            observable(date);
        });



    },
    update: function(element, valueAccessor) {
        var value = ko.utils.unwrapObservable(valueAccessor());
        // TODO verificar se value é Date ou String e configurar de acordo

        if(undefined == value) {
            return;
        }

        //console.log("retrieving date " + value);
        var date = value.split('-');
        //console.log(date);
        jQuery(element).datepicker("setDate", new Date(date[0], date[1] - 1, date[2]));
    }
};


ko.bindingHandlers.dialog = {
    init:function (element, valueAccessor, allBindingsAccessor, viewModel) {
        var $element = jQuery(element),
            params = valueAccessor();

        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
            $element.dialog("destroy");
        });

        jQuery.extend(params, { autoOpen:false });

        $element.dialog(params);
    }
};

// Initialize metaproject
(function (window) {
    window.metaproject = {
        routes:{},
        init:function (target) {
            if (typeof(target) == 'string') {
                target = $(target);
            }

            // Default UI element behaviour
            target.find('.tabs').tabs();
            //target.find('.pills').pills();
            target.find('.dropdown').dropdown();
        },
        route:function (routes) {
            this.routes = routes;
            $('div[role=page]').hide();
            $(window).on('hashchange',
                function (e) {

                    var params = window.location.hash.substr(1).split('/');

                    if (params[0] == '') {
                        params[0] = '/';
                    }

                    var page;
                    var path = metaproject.routes[params[0]];
                    if (undefined != path) {
                        if (typeof(path) == 'string') {
                            // If its an element, get the relative DOM node
                            if (path.charAt(0) == '#') {
                                page = jQuery(path);
                            }
                            else {
                                // If it's a path, include the file if necessary
                                page = jQuery('#' + params[0]);

                                if (page.length == 0) {
                                    // load and insert page into body
                                    page = $('<div id="' + params[0] + '" role="page"></div>').include(path).appendTo('div[role=main]');
                                }
                            }


                            $('div[role=page]:visible').hide();
                            page.show();


                            console.log(page);
                        }
                    }
                    else {
                        console.log('non ecsiste');
                    }
                }).trigger('hashchange');
        }
    }
})(window);

jQuery(function ($) {

    $.extend($.ui.dialog.prototype.options, { closeText:'x' });

    /* Includes and initializes another file on the element */
    $.fn.include = function (url, callback) {
        var self = this;

        if (this.data('loaded')) {
            return self;
        }
        else {
            return this.data('loaded', true).addClass('loading').load(url, function () {

                metaproject.init(self.removeClass('loading'));

                if (undefined != callback) {
                    callback();
                }
            });
        }


    };

    /* UI Alerts */
    $.fn.alert = function (kind, message) {
        var options = {
            level:kind,
            block:false,
            delay:250
        };

        $('<div class="alert-message ' + options.level + (options.block ? ' block-message' : '') + '" style="display: none;"><a class="close" href="#">×</a>' + message + '</div>')
            .prependTo(this).fadeIn(options.delay);
    };

    // Bindings for the close alert button
    $(document).on('click', ".alert-message .close", function (e) {
        e.preventDefault();
        var $element = $(this).parent('.alert-message');

        $element.fadeOut(250, function () {
            $element.remove();
        });

    });

    // Alert helpers
    $.fn.info = function (message, options) {
        this.alert('info', message);
    };

    $.fn.success = function (message) {
        this.alert('success', message);
    };

    $.fn.warning = function (message) {
        this.alert('warning', message);
    };

    $.fn.error = function (message) {
        this.alert('error', message);
    };

    // This initializes all dynamic elements on the main document
    metaproject.init($(document));


});


