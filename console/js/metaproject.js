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
//Modernizr.load({
//    test:window.JSON,
//    nope:'json2.js'
//});

// Initialize metaproject
(function (window, $) {
    window.metaproject = {
        routes:{},
        debug: 0,
        init:function (target) {
            if (typeof(target) == 'string') {
                target = $(target);
            }
        },
        route:function (routes, main_content) {
            this.routes = routes;
            if(typeof(main_content) == 'string') {
                main_content = $(main_content);
            }

            $(window).on('hashchange',
                function (e) {

                    var params = window.location.hash.substr(1).split('/');

                    if (params[0] == '') {
                        params[0] = '/';
                    }

                    var path = metaproject.routes[params[0]];
                    if (undefined != path) {
                        if (typeof(path) == 'string') {

                            var src = jQuery(path);

                            if(src.length > 0) {
                                // If its an element, get the relative DOM node
                                // TODO data('loaded') is an ugly hack
                                main_content.data('loaded', path).html(src.html());
                            }
                            else {
                                if(metaproject.debug) {
                                    path = path + '?' + new Date().time;
                                }

                                main_content.include(path);
                            }
                        }
                    }
                    else {
                        main_content.text('non ecsiste');
                    }
                }).trigger('hashchange');
        }
    };

    // Core plugins

    /* ui-dialog workaround (close button) */
    $.extend($.ui.dialog.prototype.options, { closeText:'x' });

    /* Includes and initializes another file on the element */
    $.fn.include = function (url, callback) {
        var self = this;
        if(self.data('loaded') == url) {
            return this;
        }
        else {
            return this.addClass('loading').load(url, function () {

                self.data('loaded', url).removeClass('loading');
                //metaproject.init(self.removeClass('loading'));

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

    $.fn.applyBindings = function (viewModel) {
        this.data('viewModel', viewModel).each(function(idx, element) {
            ko.applyBindings(viewModel, element);
        });
    };

    $(function () {
        // This initializes all dynamic elements on the main document
        metaproject.init($(document));
    });
})(window, jQuery);


ko.bindingHandlers.icon = {
    init:function (element, valueAccessor) {

        var icon = '<span class="ui-icon ui-icon-' + valueAccessor() + '"></span>';

        jQuery(element).prepend(icon);
    }
};

ko.bindingHandlers.include = {
    init:function (element, valueAccessor) {
        var params = valueAccessor();
        if (params instanceof Array) {
            jQuery(element).include(params[0], params[1]);
        }
        else {
            jQuery(element).include(params);
        }
    }
};

ko.bindingHandlers.autocomplete = {
    init:function (element, valueAccessor, allBindingsAccessor, viewModel) {

        var $element = jQuery(element),
            params = valueAccessor();

        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
            $element.autocomplete("destroy");
        });

        // treat String, callback or Array as source
        if (typeof(params) == 'string' || typeof(params) == 'function' || params instanceof Array) {
            params = { source:params };
        }

        var $autocomplete = $element.autocomplete(params).data('autocomplete');

        // Custom render callback http://jqueryui.com/demos/autocomplete/#custom-data
        // TODO render as string => ko templates ?
        if (undefined != params.renderItem) {
            $autocomplete._renderItem = params.renderItem;
        }

        if (undefined != params.renderMenu) {
            $autocomplete._renderMenu = params.renderMenu;
        }
    }
};




ko.bindingHandlers.dialog = {
    init:function (element, valueAccessor, allBindingsAccessor, viewModel) {
        var $element = jQuery(element),
            params = valueAccessor();

        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
            $element.dialog("destroy");
        });

        jQuery.extend(params, { autoOpen:false });

        $element.dialog(params);
    }
};

