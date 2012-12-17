(function (window, $, ko) {
    var metaproject = window.metaproject = {},
        Signal = signals.Signal;

    metaproject.Model = function (defaults, mapping) {

        return function (data) {
            var instance = this;

            data = data || {};

            $.each(defaults, function (i, e) {
                if (typeof(e) == 'function') {
                    instance[i] = ko.computed({ read:e, deferEvaluation:true }, instance);
                }
                else {
                    if (undefined == data[i]) {
                        data[i] = defaults[i];
                    }
                }
            });

            // data = $.extend({}, defaults, data);

            ko.mapping.fromJS(data, mapping || {}, instance);

        }

    };

    metaproject.DataSource = function (base_url, options) {
        var self = this,
            _navs = [];

        options = $.extend({
            key:'id',
            model:function (data) {
                $.extend(this, data);
            }
        }, options);

        self.changed = new Signal();

        self._id = function (model_or_id) {
            if (typeof(model_or_id) == 'object') {
                return ko.utils.unwrapObservable(model_or_id[options.key]);
            }
            else {
                return model_or_id;
            }
        };

        self.create = function (data) {
            return new options.model(data);
        };

        self.save = function (model, callback) {
            var id = ko.utils.unwrapObservable(model[options.key]);
            if (id) {
                return self.put(id, ko.mapping.toJSON(model), callback);
            }
            else {
                return self.post(ko.mapping.toJSON(model), callback);
            }
        };

        // get(path || {model}, params);
        // get(path || {model}, params, callback);
        // get(path || {model}, callback);
        self.get = function (path, params, callback) {

            // get({model})
            if (typeof(path) != 'string') {
                // TODO existe path[key] ?
                path = '/' + ko.utils.unwrapObservable(path[options.key]);
            }
            else if (path[0] != '/') {
                path = '/' + path;
            }

            if (typeof(params) == 'function') {
                callback = params;
                params = {}
            }

            return jQuery.ajax({
                    url:base_url + path,
                    data:params || {},
                    dataType:'json',
                    type:'GET',
                    error:self.errorHandler,
                    success:function (data) {
                        if (typeof(callback) == 'function') {
                            if (data instanceof Array) {
                                callback(jQuery.map(data, function (e, i) {
                                    return new options.model(e);
                                }));
                            }
                            else {
                                callback(new options.model(data));
                            }
                        }
                    }
                }
            );
        };

        self.post = function (data, callback) {
            return jQuery.ajax({
                url:base_url,
                dataType:'json',
                type:'POST',
                data:data,
                success:function (data) {
                    self.changed.dispatch('post', data);

                    if (typeof(callback) === 'function') {
                        callback(data);
                    }
                },
                error:self.errorHandler
            });
        };

        self.put = function (id, data, callback) {
            return jQuery.ajax({
                url:base_url + '/' + id,
                dataType:'json',
                type:'PUT',
                data:data,
                success:function (data) {
                    self.changed.dispatch('put', data);
                    if (typeof(callback) === 'function') {
                        callback(data);
                    }
                },
                error:self.errorHandler
            });
        };

        self.delete = function (model, callback) {
            return jQuery.ajax({
                url:base_url + '/' + self._id(model),
                dataType:'json',
                type:'DELETE',
                success:function (data) {
                    self.changed.dispatch('delete', data);
                    if (typeof(callback) === 'function') {
                        callback(data);
                    }
                },
                error:self.errorHandler
            });
        };

        // Editor for this DataSource
        self.Editor = function (callbacks) {
            var ds = self,
                editor = this;

            // TODO listen for changes, check if current() = changed, notify ui
            callbacks = $.extend({
                save:function () {
                    //ds.data.reload();
                }
            }, callbacks);

            editor.current = ko.observable(null);

            editor.create = function (values) {
                editor.current(ds.create(values));
            };

            editor.delete = function() {

                ds.delete(editor.current());

                if (typeof(callbacks.delete) == 'function') {
                    callbacks.delete();
                }
            };

            editor.load = function (model) {
                ds.get(model, editor.current);
            };

            editor.close = function () {
                editor.current(null);

                if (typeof(callbacks.close) == 'function') {
                    callbacks.close();
                }
            };

            editor.save = function () {
                return ds.save(editor.current(), callbacks.save);
            };
        };


        // an observable that retrieves its value when first bound
        // From http://www.knockmeout.net/2011/06/lazy-loading-observable-in-knockoutjs.html
        self.Nav = function (filter) {

            var _value = ko.observable(),
                _hash = ko.observable(null);

            var result = ko.computed({
                read:function () {
                    var newhash = ko.toJSON(result.filter()) + result.page();
                    if (_hash() != newhash) {
                        self.get('/', result.filter(), function (newData) {
                            _hash(newhash);
                            _value(newData);
                        });
                    }

                    //always return the current value
                    return _value();
                },
                write: _value,
                deferEvaluation:true  //do not evaluate immediately when created
            });

            result.page = ko.observable(0);
            result.page.total = ko.observable(0);
            result.page.next = function () {
                if (result.page.total() > result.page()) {
                    result.page(result.page() + 1);
                }
            };
            result.page.prev = function () {
                if (result.page() > 1) {
                    result.page(result.page() - 1);
                }
            };

            result.filter = ko.observable(filter || {});
            result.filter.set = function (param, value) {
                result.filter()[param] = value;
                result.filter.valueHasMutated();
            };

            result.reload = function () {
                _hash(null);
            };

            // Reload when datasource is updated
            self.changed.add(result.reload);
            return result;
        };

    };

    metaproject.Application = function (params) {
        var self = this;

        self.debug = 0;

        self.init = function () {

        };

        $.extend(this, params);

        self.run = function () {
            ko.applyBindings(self);
            self.init.call(self);
        };

    };

    metaproject.Loader = function (routes, params) {
        var options = {
            'default':'/',
            error:function (e) {
                alert(e.responseText);
            }
        };

        $.extend(options, params);

        var _content = ko.observable(null);

        _content.id = ko.observable(null);

        _content.load = function (id, callback) {

            // default = /
            if (undefined == id || id == '') {
                id = '/';
            }

            if (id == _content.id()) {
                return;
            }

            var path = routes[id];

            if (undefined == routes[id]) {
                _content.id(null);
                _content('Route ' + id + ' not found');
                return;
            }

            if (typeof(path) == 'string') {

                if (path[0] == '#') {
                    var src = jQuery(path);

                    if (src.length > 0) { // If its an element, get the relative DOM node
                        _content(null);
                        _content.id(id);
                        _content(src.html());
                        if (typeof(callback) == 'function') {
                            callback();
                        }

                    }
                    else {
                        _content.id(null);
                        _content('Element ' + path + ' not found');
                    }
                }
                else {
                    var params = {};

                    if (metaproject.debug) {
                        params.ts = new Date().getTime();
                    }

                    $.ajax({
                        url:path,
                        type:'GET',
                        data:params,
                        dataType:'html',
                        success:function (data) {
                            _content(null);
                            _content.id(id);
                            _content(data);

                            if (typeof(callback) == 'function') {
                                callback();
                            }

                        },
                        error:function (e) {
                            _content.id(null);
                            _content(null);
                            options.error(e);
                        }
                    });
                }
            }
        };

        _content.load(options['default']);
        return _content;
    };


})(window, jQuery, ko);

// Initialize metaproject
(function (window, $) {

    // Core plugins

    /* ui-dialog workaround (close button) */
    //$.extend($.ui.dialog.prototype.options, { closeText:'x' });

    /* Includes and initializes another file on the element */
    $.fn.include = function (url, callback) {
        var self = this;
        if (self.data('loaded') == url) {
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
        this.data('viewModel', viewModel).each(function (idx, element) {
            ko.applyBindings(viewModel, element);
        });
    };

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

        //handle disposal
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

