/*global alert: true, jQuery: true, ko: true */
(function (window, $, ko) {
    "use strict";

    var metaproject = window.metaproject = {};

    metaproject.Application = function (params) {
        var self = this;

        self.debug = 0;

        if (typeof(params) === 'function') {
            self.init = params;
        }
        else {
            self.init = function () {
            };
            $.extend(this, params);
        }

        self.run = function (element) {
            ko.applyBindings(self, element);
            self.init.call(self);
            $(window).trigger('hashchange');
        };

    };

    /* jQuery plugs */

    /**
     * Shortcut to ko.applyBindings, save the viewModel on data-viewModel
     * @param viewModel
     */
    $.fn.applyBindings = function (viewModel) {
        this.data('viewModel', viewModel).each(function (idx, element) {
            ko.applyBindings(viewModel, element);
        });
    };

    /**
     * Includes and initializes another file on the element
     * @param url
     * @param callback optional, runs after the url is loaded
     * @return {*}
     */
    $.fn.include = function (url, callback) {
        var self = this,
            params = metaproject.debug ? '?ts=' + new Date().getTime() : '';

        if (self.data('loaded') === url) {
            return this;
        }
        else {
            return this.addClass('loading').load(url + params, function () {

                self.data('loaded', url).removeClass('loading');
                //metaproject.init(self.removeClass('loading'));

                if (undefined !== callback) {
                    callback();
                }
            });
        }

    };

    /* Custom Binding handlers */

    // Includes an external file on the DOM element
    ko.bindingHandlers.include = {
        init: function (element, valueAccessor, allBindingsAccessor) {
            var $element = $(element),
                params = valueAccessor(),
                url = allBindingsAccessor().url;

            if (params instanceof Array) {
                $element.include(params[0], params[1]);
            }
            else {
                $element.include(params);
            }

            // If there's no url assigned to this node, activate it
            // (Otherwise it will be activated according to the hash)
            if (!url) {
                $element.children().trigger('activate', $element);
            }
        }
    };

    // Attach an url controller to this node
    // The node will receive activate and deactivate events when the url changes
    ko.bindingHandlers.url = {
        init: function (element, valueAccessor, allBindingsAccessor) {
            var $element = $(element),
                url = valueAccessor();

            $element.css({ visibility: 'hidden', position: 'absolute', height: 0, overflow: 'hidden' });

            $(window).on('hashchange', function (e) {
                var hash = window.location.hash.substr(1) || '/';

                if (hash === url) {
                    if ($element.css('visibility') !== 'visible') {
                        $element.css({ visibility: 'visible', position: 'inherit', height: 'auto', overflow: 'inherit' }).children().trigger('activate', [ element, hash ]);
                    }
                }
                else {
                    if ($element.css('visibility') === 'visible') {
                        $element.css({ visibility: 'hidden', position: 'absolute', height: 0, overflow: 'hidden' }).children().trigger('deactivate', [$element, hash]);
                    }
                }
            });


            // TODO dispose callback
        }
    };

})(window, jQuery, ko);
/*global jQuery: true, ko: true, Chart: true */
(function($, ko, Chart) {
    "use strict";

    // TODO queue rendering, limit to 1 chart at a time ?

    ko.bindingHandlers.chart = {
        init:function (element, valueAccessor, allBindingsAccessor) {
            var
                ctx = element.getContext('2d'),
                chart = new Chart(ctx);

            chart._type = element.dataset.chart || 'line';

            $(element).data('chart', chart);

            ko.bindingHandlers.chart.update(element, valueAccessor, allBindingsAccessor);
        },
        update:function (element, valueAccessor, allBindingsAccessor) {
            var data = ko.utils.unwrapObservable(valueAccessor()),
                options = allBindingsAccessor().chartOptions,
                chart = $(element).data('chart');

            switch(chart._type) {
                case 'line':
                    chart.Line(data,options);
                    break;
                case 'bar':
                    chart.Bar(data,options);
                    break;
                case 'radar':
                    chart.Radar(data, options);
                    break;
                case 'polar':
                    chart.PolarArea(data,options);
                    break;
                case 'pie':
                    chart.Pie(data, options);
                    break;
                case 'doughnut':
                    chart.Doughnut(data, options);
                    break;
                default:
                    throw 'invalid chart type';

            }
        }
    };

}(jQuery, ko, Chart));/*global alert: true, jQuery: true, ko: true */
(function (window, $, ko) {
    "use strict";

    var metaproject = window.metaproject || {};

    metaproject.DataSource = function (base_url, options) {
        var self = this,
            $self = $(this),
            _navs = [];

        options = $.extend({
            key: 'id',
            model: function (data) {
                $.extend(this, data);
            }
        }, options);

        // Events
        self.on = function () {
            $self.on.apply($self, arguments);
        };

        self._id = function (model_or_id) {
            if (typeof(model_or_id) === 'object') {
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
            if (typeof(path) !== 'string') {
                // TODO existe path[key] ?
                path = '/' + ko.utils.unwrapObservable(path[options.key]);
            }
            else if (path[0] !== '/') {
                path = '/' + path;
            }

            if (typeof(params) === 'function') {
                callback = params;
                params = {};
            }

            return $.ajax({
                    url: base_url + path,
                    data: params || {},
                    dataType: 'json',
                    type: 'GET',
                    error: self.errorHandler,
                    success: function (data) {
                        if (typeof(callback) === 'function') {
                            if (data instanceof Array) {
                                callback($.map(data, function (e, i) {
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
            // TODO datasource.post(path, data, callback)
            return $.ajax({
                url: base_url,
                dataType: 'json',
                type: 'POST',
                data: data,
                success: function (data) {
                    $self.trigger('changed', { action: 'post', data: data});
                    if (typeof(callback) === 'function') {
                        callback(data);
                    }
                },
                error: self.errorHandler
            });
        };

        self.put = function (id, data, callback) {
            // TODO datasource.put(model, callback)
            return $.ajax({
                url: base_url + '/' + id,
                dataType: 'json',
                type: 'PUT',
                data: data,
                success: function (data) {
                    $self.trigger('changed', { action: 'put', data: data});
                    if (typeof(callback) === 'function') {
                        callback(data);
                    }
                },
                error: self.errorHandler
            });
        };

        self.destroy = function (model, callback) {
            return $.ajax({
                url: base_url + '/' + self._id(model),
                dataType: 'json',
                type: 'DELETE',
                success: function (data) {
                    $self.trigger('changed', { action: 'destroy', data: data});
                    if (typeof(callback) === 'function') {
                        callback(data);
                    }
                },
                error: self.errorHandler
            });
        };

        // Editor for this DataSource
        self.Editor = function (callbacks) {
            var ds = self,
                editor = this;

            callbacks = $.extend({
                save: function () {
                    //ds.data.reload();
                }
            }, callbacks);

            editor.current = ko.observable(null);

            editor.create = function (values) {
                editor.current(ds.create(values));
            };

            editor.destroy = function () {

                self.destroy(editor.current(), function() {
                    if (typeof(callbacks.destroy) === 'function') {
                        callbacks.destroy();
                    }
                });
            };

            editor.load = function (model) {
                self.get(model, editor.current);
            };

            editor.close = function () {
                editor.current(null);

                if (typeof(callbacks.close) === 'function') {
                    callbacks.close();
                }
            };

            editor.save = function () {
                return self.save(editor.current(), callbacks.save);
            };
        };


        // an observable that retrieves its value when first bound
        // From http://www.knockmeout.net/2011/06/lazy-loading-observable-in-knockoutjs.html
        self.Nav = function (filter) {

            var _value = ko.observable(), // current value
                _filter = ko.observable(filter || {}), // the filter
                _observables = [], // list of instantiated observables
                _hash = ko.observable(null);

            var result = ko.computed({
                read: function () {
                    var newhash = ko.toJSON(_filter());
                    if (_hash() !== newhash) {
                        result.loading(true);
                        self.get('/', _filter(), function (newData) {
                            _hash(newhash);
                            _value(newData);

                            // TODO generic trigger/on for objects
                            $.each(_observables, function (i, o) {
                                o.reload();
                            });
                            result.loading(false);
                        });
                    }

                    //always return the current value
                    return _value();
                },
                write: _value,
                deferEvaluation: true  //do not evaluate immediately when created
            });
            result.loading = ko.observable(false);
            result._live = true; // update this navigato

            result.filter = _filter;
            result.filter.set = function (param, value) {
                result.filter()[param] = value;
                result.filter.valueHasMutated();
            };

            /**
             * Resets filter, leaving _* parameters unchanged
             * @param data
             */
            result.filter.reset = function (data, notify) {
                data = data || {};

                data._offset = 0;
                var filter = result.filter();

                _.keys(filter).forEach(function (key) {
                    if (key[0] !== '_') {
                        delete filter[key];
                    }
                });

                _.extend(filter, data);

                if (notify) {
                    result.filter.valueHasMutated();
                }
            };

            result.observable = function (params, transform) {
                var me = ko.observable(null);
                me.loading = ko.observable(false);
                me.reload = function () {
                    me.loading(true);
                    var x = _.filter(_.keys(result.filter()), function (value, index, list) {
                        return value[0] !== '_';
                    });
                    var local_params = _.extend(_.pick(result.filter(), x), params);

                    self.get('/', local_params, function (newData) {
                        if (typeof(transform) === 'function') {
                            me(transform(newData));
                        }
                        else {
                            me(newData);
                        }
                        me.loading(false);
                    });
                };


                _observables.push(me);

                return me;
            };

            result.reload = function () {
                _hash(null);
            };

            // Reload when datasource is updated
            self.on('changed', function () {
                if (result._live) {
                    result.reload();
                }
            });

            return result;
        };

    };

    metaproject.Model = function (defaults, mapping) {

        return function (data) {
            var instance = this;

            data = data || {};

            $.each(defaults, function (i, e) {
                if (typeof(e) === 'function') {
                    instance[i] = ko.computed({ read: e, deferEvaluation: true }, instance);
                }
                else {
                    if (undefined === data[i]) {
                        data[i] = defaults[i];
                    }
                }
            });

            // data = $.extend({}, defaults, data);

            ko.mapping.fromJS(data, mapping || {}, instance);

        };

    };

})(window, jQuery, ko);/*global jQuery:true, ko:true */
// Datepicker input
// Depends on jquery-ui


/**
 * Version: 1.0 Alpha-1
 * Build Date: 13-Nov-2007
 * Copyright (c) 2006-2007, Coolite Inc. (http://www.coolite.com/). All rights reserved.
 * License: Licensed under The MIT License. See license.txt and http://www.datejs.com/license/.
 * Website: http://www.datejs.com/ or http://www.coolite.com/datejs/
 */
Date.CultureInfo = {name:"en-US", englishName:"English (United States)", nativeName:"English (United States)", dayNames:["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"], abbreviatedDayNames:["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"], shortestDayNames:["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"], firstLetterDayNames:["S", "M", "T", "W", "T", "F", "S"], monthNames:["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"], abbreviatedMonthNames:["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"], amDesignator:"AM", pmDesignator:"PM", firstDayOfWeek:0, twoDigitYearMax:2029, dateElementOrder:"mdy", formatPatterns:{shortDate:"M/d/yyyy", longDate:"dddd, MMMM dd, yyyy", shortTime:"h:mm tt", longTime:"h:mm:ss tt", fullDateTime:"dddd, MMMM dd, yyyy h:mm:ss tt", sortableDateTime:"yyyy-MM-ddTHH:mm:ss", universalSortableDateTime:"yyyy-MM-dd HH:mm:ssZ", rfc1123:"ddd, dd MMM yyyy HH:mm:ss GMT", monthDay:"MMMM dd", yearMonth:"MMMM, yyyy"}, regexPatterns:{jan:/^jan(uary)?/i, feb:/^feb(ruary)?/i, mar:/^mar(ch)?/i, apr:/^apr(il)?/i, may:/^may/i, jun:/^jun(e)?/i, jul:/^jul(y)?/i, aug:/^aug(ust)?/i, sep:/^sep(t(ember)?)?/i, oct:/^oct(ober)?/i, nov:/^nov(ember)?/i, dec:/^dec(ember)?/i, sun:/^su(n(day)?)?/i, mon:/^mo(n(day)?)?/i, tue:/^tu(e(s(day)?)?)?/i, wed:/^we(d(nesday)?)?/i, thu:/^th(u(r(s(day)?)?)?)?/i, fri:/^fr(i(day)?)?/i, sat:/^sa(t(urday)?)?/i, future:/^next/i, past:/^last|past|prev(ious)?/i, add:/^(\+|after|from)/i, subtract:/^(\-|before|ago)/i, yesterday:/^yesterday/i, today:/^t(oday)?/i, tomorrow:/^tomorrow/i, now:/^n(ow)?/i, millisecond:/^ms|milli(second)?s?/i, second:/^sec(ond)?s?/i, minute:/^min(ute)?s?/i, hour:/^h(ou)?rs?/i, week:/^w(ee)?k/i, month:/^m(o(nth)?s?)?/i, day:/^d(ays?)?/i, year:/^y((ea)?rs?)?/i, shortMeridian:/^(a|p)/i, longMeridian:/^(a\.?m?\.?|p\.?m?\.?)/i, timezone:/^((e(s|d)t|c(s|d)t|m(s|d)t|p(s|d)t)|((gmt)?\s*(\+|\-)\s*\d\d\d\d?)|gmt)/i, ordinalSuffix:/^\s*(st|nd|rd|th)/i, timeContext:/^\s*(\:|a|p)/i}, abbreviatedTimeZoneStandard:{GMT:"-000", EST:"-0400", CST:"-0500", MST:"-0600", PST:"-0700"}, abbreviatedTimeZoneDST:{GMT:"-000", EDT:"-0500", CDT:"-0600", MDT:"-0700", PDT:"-0800"}};
Date.getMonthNumberFromName = function (name) {
    var n = Date.CultureInfo.monthNames, m = Date.CultureInfo.abbreviatedMonthNames, s = name.toLowerCase();
    for (var i = 0; i < n.length; i++) {
        if (n[i].toLowerCase() == s || m[i].toLowerCase() == s) {
            return i;
        }
    }
    return-1;
};
Date.getDayNumberFromName = function (name) {
    var n = Date.CultureInfo.dayNames, m = Date.CultureInfo.abbreviatedDayNames, o = Date.CultureInfo.shortestDayNames, s = name.toLowerCase();
    for (var i = 0; i < n.length; i++) {
        if (n[i].toLowerCase() == s || m[i].toLowerCase() == s) {
            return i;
        }
    }
    return-1;
};
Date.isLeapYear = function (year) {
    return(((year % 4 === 0) && (year % 100 !== 0)) || (year % 400 === 0));
};
Date.getDaysInMonth = function (year, month) {
    return[31, (Date.isLeapYear(year) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month];
};
Date.getTimezoneOffset = function (s, dst) {
    return(dst || false) ? Date.CultureInfo.abbreviatedTimeZoneDST[s.toUpperCase()] : Date.CultureInfo.abbreviatedTimeZoneStandard[s.toUpperCase()];
};
Date.getTimezoneAbbreviation = function (offset, dst) {
    var n = (dst || false) ? Date.CultureInfo.abbreviatedTimeZoneDST : Date.CultureInfo.abbreviatedTimeZoneStandard, p;
    for (p in n) {
        if (n[p] === offset) {
            return p;
        }
    }
    return null;
};
Date.prototype.clone = function () {
    return new Date(this.getTime());
};
Date.prototype.compareTo = function (date) {
    if (isNaN(this)) {
        throw new Error(this);
    }
    if (date instanceof Date && !isNaN(date)) {
        return(this > date) ? 1 : (this < date) ? -1 : 0;
    } else {
        throw new TypeError(date);
    }
};
Date.prototype.equals = function (date) {
    return(this.compareTo(date) === 0);
};
Date.prototype.between = function (start, end) {
    var t = this.getTime();
    return t >= start.getTime() && t <= end.getTime();
};
Date.prototype.addMilliseconds = function (value) {
    this.setMilliseconds(this.getMilliseconds() + value);
    return this;
};
Date.prototype.addSeconds = function (value) {
    return this.addMilliseconds(value * 1000);
};
Date.prototype.addMinutes = function (value) {
    return this.addMilliseconds(value * 60000);
};
Date.prototype.addHours = function (value) {
    return this.addMilliseconds(value * 3600000);
};
Date.prototype.addDays = function (value) {
    return this.addMilliseconds(value * 86400000);
};
Date.prototype.addWeeks = function (value) {
    return this.addMilliseconds(value * 604800000);
};
Date.prototype.addMonths = function (value) {
    var n = this.getDate();
    this.setDate(1);
    this.setMonth(this.getMonth() + value);
    this.setDate(Math.min(n, this.getDaysInMonth()));
    return this;
};
Date.prototype.addYears = function (value) {
    return this.addMonths(value * 12);
};
Date.prototype.add = function (config) {
    if (typeof config == "number") {
        this._orient = config;
        return this;
    }
    var x = config;
    if (x.millisecond || x.milliseconds) {
        this.addMilliseconds(x.millisecond || x.milliseconds);
    }
    if (x.second || x.seconds) {
        this.addSeconds(x.second || x.seconds);
    }
    if (x.minute || x.minutes) {
        this.addMinutes(x.minute || x.minutes);
    }
    if (x.hour || x.hours) {
        this.addHours(x.hour || x.hours);
    }
    if (x.month || x.months) {
        this.addMonths(x.month || x.months);
    }
    if (x.year || x.years) {
        this.addYears(x.year || x.years);
    }
    if (x.day || x.days) {
        this.addDays(x.day || x.days);
    }
    return this;
};
Date._validate = function (value, min, max, name) {
    if (typeof value != "number") {
        throw new TypeError(value + " is not a Number.");
    } else if (value < min || value > max) {
        throw new RangeError(value + " is not a valid value for " + name + ".");
    }
    return true;
};
Date.validateMillisecond = function (n) {
    return Date._validate(n, 0, 999, "milliseconds");
};
Date.validateSecond = function (n) {
    return Date._validate(n, 0, 59, "seconds");
};
Date.validateMinute = function (n) {
    return Date._validate(n, 0, 59, "minutes");
};
Date.validateHour = function (n) {
    return Date._validate(n, 0, 23, "hours");
};
Date.validateDay = function (n, year, month) {
    return Date._validate(n, 1, Date.getDaysInMonth(year, month), "days");
};
Date.validateMonth = function (n) {
    return Date._validate(n, 0, 11, "months");
};
Date.validateYear = function (n) {
    return Date._validate(n, 1, 9999, "seconds");
};
Date.prototype.set = function (config) {
    var x = config;
    if (!x.millisecond && x.millisecond !== 0) {
        x.millisecond = -1;
    }
    if (!x.second && x.second !== 0) {
        x.second = -1;
    }
    if (!x.minute && x.minute !== 0) {
        x.minute = -1;
    }
    if (!x.hour && x.hour !== 0) {
        x.hour = -1;
    }
    if (!x.day && x.day !== 0) {
        x.day = -1;
    }
    if (!x.month && x.month !== 0) {
        x.month = -1;
    }
    if (!x.year && x.year !== 0) {
        x.year = -1;
    }
    if (x.millisecond != -1 && Date.validateMillisecond(x.millisecond)) {
        this.addMilliseconds(x.millisecond - this.getMilliseconds());
    }
    if (x.second != -1 && Date.validateSecond(x.second)) {
        this.addSeconds(x.second - this.getSeconds());
    }
    if (x.minute != -1 && Date.validateMinute(x.minute)) {
        this.addMinutes(x.minute - this.getMinutes());
    }
    if (x.hour != -1 && Date.validateHour(x.hour)) {
        this.addHours(x.hour - this.getHours());
    }
    if (x.month !== -1 && Date.validateMonth(x.month)) {
        this.addMonths(x.month - this.getMonth());
    }
    if (x.year != -1 && Date.validateYear(x.year)) {
        this.addYears(x.year - this.getFullYear());
    }
    if (x.day != -1 && Date.validateDay(x.day, this.getFullYear(), this.getMonth())) {
        this.addDays(x.day - this.getDate());
    }
    if (x.timezone) {
        this.setTimezone(x.timezone);
    }
    if (x.timezoneOffset) {
        this.setTimezoneOffset(x.timezoneOffset);
    }
    return this;
};
Date.prototype.clearTime = function () {
    this.setHours(0);
    this.setMinutes(0);
    this.setSeconds(0);
    this.setMilliseconds(0);
    return this;
};
Date.prototype.isLeapYear = function () {
    var y = this.getFullYear();
    return(((y % 4 === 0) && (y % 100 !== 0)) || (y % 400 === 0));
};
Date.prototype.isWeekday = function () {
    return!(this.is().sat() || this.is().sun());
};
Date.prototype.getDaysInMonth = function () {
    return Date.getDaysInMonth(this.getFullYear(), this.getMonth());
};
Date.prototype.moveToFirstDayOfMonth = function () {
    return this.set({day:1});
};
Date.prototype.moveToLastDayOfMonth = function () {
    return this.set({day:this.getDaysInMonth()});
};
Date.prototype.moveToDayOfWeek = function (day, orient) {
    var diff = (day - this.getDay() + 7 * (orient || +1)) % 7;
    return this.addDays((diff === 0) ? diff += 7 * (orient || +1) : diff);
};
Date.prototype.moveToMonth = function (month, orient) {
    var diff = (month - this.getMonth() + 12 * (orient || +1)) % 12;
    return this.addMonths((diff === 0) ? diff += 12 * (orient || +1) : diff);
};
Date.prototype.getDayOfYear = function () {
    return Math.floor((this - new Date(this.getFullYear(), 0, 1)) / 86400000);
};
Date.prototype.getWeekOfYear = function (firstDayOfWeek) {
    var y = this.getFullYear(), m = this.getMonth(), d = this.getDate();
    var dow = firstDayOfWeek || Date.CultureInfo.firstDayOfWeek;
    var offset = 7 + 1 - new Date(y, 0, 1).getDay();
    if (offset == 8) {
        offset = 1;
    }
    var daynum = ((Date.UTC(y, m, d, 0, 0, 0) - Date.UTC(y, 0, 1, 0, 0, 0)) / 86400000) + 1;
    var w = Math.floor((daynum - offset + 7) / 7);
    if (w === dow) {
        y--;
        var prevOffset = 7 + 1 - new Date(y, 0, 1).getDay();
        if (prevOffset == 2 || prevOffset == 8) {
            w = 53;
        } else {
            w = 52;
        }
    }
    return w;
};
Date.prototype.isDST = function () {
    return this.toString().match(/(E|C|M|P)(S|D)T/)[2] == "D";
};
Date.prototype.getTimezone = function () {
    return Date.getTimezoneAbbreviation(this.getUTCOffset, this.isDST());
};
Date.prototype.setTimezoneOffset = function (s) {
    var here = this.getTimezoneOffset(), there = Number(s) * -6 / 10;
    this.addMinutes(there - here);
    return this;
};
Date.prototype.setTimezone = function (s) {
    return this.setTimezoneOffset(Date.getTimezoneOffset(s));
};
Date.prototype.getUTCOffset = function () {
    var n = this.getTimezoneOffset() * -10 / 6, r;
    if (n < 0) {
        r = (n - 10000).toString();
        return r[0] + r.substr(2);
    } else {
        r = (n + 10000).toString();
        return"+" + r.substr(1);
    }
};
Date.prototype.getDayName = function (abbrev) {
    return abbrev ? Date.CultureInfo.abbreviatedDayNames[this.getDay()] : Date.CultureInfo.dayNames[this.getDay()];
};
Date.prototype.getMonthName = function (abbrev) {
    return abbrev ? Date.CultureInfo.abbreviatedMonthNames[this.getMonth()] : Date.CultureInfo.monthNames[this.getMonth()];
};
Date.prototype._toString = Date.prototype.toString;
Date.prototype.toString = function (format) {
    var self = this;
    var p = function p(s) {
        return(s.toString().length == 1) ? "0" + s : s;
    };
    return format ? format.replace(/dd?d?d?|MM?M?M?|yy?y?y?|hh?|HH?|mm?|ss?|tt?|zz?z?/g, function (format) {
        switch (format) {
            case"hh":
                return p(self.getHours() < 13 ? self.getHours() : (self.getHours() - 12));
            case"h":
                return self.getHours() < 13 ? self.getHours() : (self.getHours() - 12);
            case"HH":
                return p(self.getHours());
            case"H":
                return self.getHours();
            case"mm":
                return p(self.getMinutes());
            case"m":
                return self.getMinutes();
            case"ss":
                return p(self.getSeconds());
            case"s":
                return self.getSeconds();
            case"yyyy":
                return self.getFullYear();
            case"yy":
                return self.getFullYear().toString().substring(2, 4);
            case"dddd":
                return self.getDayName();
            case"ddd":
                return self.getDayName(true);
            case"dd":
                return p(self.getDate());
            case"d":
                return self.getDate().toString();
            case"MMMM":
                return self.getMonthName();
            case"MMM":
                return self.getMonthName(true);
            case"MM":
                return p((self.getMonth() + 1));
            case"M":
                return self.getMonth() + 1;
            case"t":
                return self.getHours() < 12 ? Date.CultureInfo.amDesignator.substring(0, 1) : Date.CultureInfo.pmDesignator.substring(0, 1);
            case"tt":
                return self.getHours() < 12 ? Date.CultureInfo.amDesignator : Date.CultureInfo.pmDesignator;
            case"zzz":
            case"zz":
            case"z":
                return"";
        }
    }) : this._toString();
};
Date.now = function () {
    return new Date();
};
Date.today = function () {
    return Date.now().clearTime();
};
Date.prototype._orient = +1;
Date.prototype.next = function () {
    this._orient = +1;
    return this;
};
Date.prototype.last = Date.prototype.prev = Date.prototype.previous = function () {
    this._orient = -1;
    return this;
};
Date.prototype._is = false;
Date.prototype.is = function () {
    this._is = true;
    return this;
};
Number.prototype._dateElement = "day";
Number.prototype.fromNow = function () {
    var c = {};
    c[this._dateElement] = this;
    return Date.now().add(c);
};
Number.prototype.ago = function () {
    var c = {};
    c[this._dateElement] = this * -1;
    return Date.now().add(c);
};
(function () {
    var $D = Date.prototype, $N = Number.prototype;
    var dx = ("sunday monday tuesday wednesday thursday friday saturday").split(/\s/), mx = ("january february march april may june july august september october november december").split(/\s/), px = ("Millisecond Second Minute Hour Day Week Month Year").split(/\s/), de;
    var df = function (n) {
        return function () {
            if (this._is) {
                this._is = false;
                return this.getDay() == n;
            }
            return this.moveToDayOfWeek(n, this._orient);
        };
    };
    for (var i = 0; i < dx.length; i++) {
        $D[dx[i]] = $D[dx[i].substring(0, 3)] = df(i);
    }
    var mf = function (n) {
        return function () {
            if (this._is) {
                this._is = false;
                return this.getMonth() === n;
            }
            return this.moveToMonth(n, this._orient);
        };
    };
    for (var j = 0; j < mx.length; j++) {
        $D[mx[j]] = $D[mx[j].substring(0, 3)] = mf(j);
    }
    var ef = function (j) {
        return function () {
            if (j.substring(j.length - 1) != "s") {
                j += "s";
            }
            return this["add" + j](this._orient);
        };
    };
    var nf = function (n) {
        return function () {
            this._dateElement = n;
            return this;
        };
    };
    for (var k = 0; k < px.length; k++) {
        de = px[k].toLowerCase();
        $D[de] = $D[de + "s"] = ef(px[k]);
        $N[de] = $N[de + "s"] = nf(de);
    }
}());
Date.prototype.toJSONString = function () {
    return this.toString("yyyy-MM-ddThh:mm:ssZ");
};
Date.prototype.toShortDateString = function () {
    return this.toString(Date.CultureInfo.formatPatterns.shortDatePattern);
};
Date.prototype.toLongDateString = function () {
    return this.toString(Date.CultureInfo.formatPatterns.longDatePattern);
};
Date.prototype.toShortTimeString = function () {
    return this.toString(Date.CultureInfo.formatPatterns.shortTimePattern);
};
Date.prototype.toLongTimeString = function () {
    return this.toString(Date.CultureInfo.formatPatterns.longTimePattern);
};
Date.prototype.getOrdinal = function () {
    switch (this.getDate()) {
        case 1:
        case 21:
        case 31:
            return"st";
        case 2:
        case 22:
            return"nd";
        case 3:
        case 23:
            return"rd";
        default:
            return"th";
    }
};
(function () {
    Date.Parsing = {Exception:function (s) {
        this.message = "Parse error at '" + s.substring(0, 10) + " ...'";
    }};
    var $P = Date.Parsing;
    var _ = $P.Operators = {rtoken:function (r) {
        return function (s) {
            var mx = s.match(r);
            if (mx) {
                return([mx[0], s.substring(mx[0].length)]);
            } else {
                throw new $P.Exception(s);
            }
        };
    }, token:function (s) {
        return function (s) {
            return _.rtoken(new RegExp("^\s*" + s + "\s*"))(s);
        };
    }, stoken:function (s) {
        return _.rtoken(new RegExp("^" + s));
    }, until:function (p) {
        return function (s) {
            var qx = [], rx = null;
            while (s.length) {
                try {
                    rx = p.call(this, s);
                } catch (e) {
                    qx.push(rx[0]);
                    s = rx[1];
                    continue;
                }
                break;
            }
            return[qx, s];
        };
    }, many:function (p) {
        return function (s) {
            var rx = [], r = null;
            while (s.length) {
                try {
                    r = p.call(this, s);
                } catch (e) {
                    return[rx, s];
                }
                rx.push(r[0]);
                s = r[1];
            }
            return[rx, s];
        };
    }, optional:function (p) {
        return function (s) {
            var r = null;
            try {
                r = p.call(this, s);
            } catch (e) {
                return[null, s];
            }
            return[r[0], r[1]];
        };
    }, not:function (p) {
        return function (s) {
            try {
                p.call(this, s);
            } catch (e) {
                return[null, s];
            }
            throw new $P.Exception(s);
        };
    }, ignore:function (p) {
        return p ? function (s) {
            var r = null;
            r = p.call(this, s);
            return[null, r[1]];
        } : null;
    }, product:function () {
        var px = arguments[0], qx = Array.prototype.slice.call(arguments, 1), rx = [];
        for (var i = 0; i < px.length; i++) {
            rx.push(_.each(px[i], qx));
        }
        return rx;
    }, cache:function (rule) {
        var cache = {}, r = null;
        return function (s) {
            try {
                r = cache[s] = (cache[s] || rule.call(this, s));
            } catch (e) {
                r = cache[s] = e;
            }
            if (r instanceof $P.Exception) {
                throw r;
            } else {
                return r;
            }
        };
    }, any:function () {
        var px = arguments;
        return function (s) {
            var r = null;
            for (var i = 0; i < px.length; i++) {
                if (px[i] == null) {
                    continue;
                }
                try {
                    r = (px[i].call(this, s));
                } catch (e) {
                    r = null;
                }
                if (r) {
                    return r;
                }
            }
            throw new $P.Exception(s);
        };
    }, each:function () {
        var px = arguments;
        return function (s) {
            var rx = [], r = null;
            for (var i = 0; i < px.length; i++) {
                if (px[i] == null) {
                    continue;
                }
                try {
                    r = (px[i].call(this, s));
                } catch (e) {
                    throw new $P.Exception(s);
                }
                rx.push(r[0]);
                s = r[1];
            }
            return[rx, s];
        };
    }, all:function () {
        var px = arguments, _ = _;
        return _.each(_.optional(px));
    }, sequence:function (px, d, c) {
        d = d || _.rtoken(/^\s*/);
        c = c || null;
        if (px.length == 1) {
            return px[0];
        }
        return function (s) {
            var r = null, q = null;
            var rx = [];
            for (var i = 0; i < px.length; i++) {
                try {
                    r = px[i].call(this, s);
                } catch (e) {
                    break;
                }
                rx.push(r[0]);
                try {
                    q = d.call(this, r[1]);
                } catch (ex) {
                    q = null;
                    break;
                }
                s = q[1];
            }
            if (!r) {
                throw new $P.Exception(s);
            }
            if (q) {
                throw new $P.Exception(q[1]);
            }
            if (c) {
                try {
                    r = c.call(this, r[1]);
                } catch (ey) {
                    throw new $P.Exception(r[1]);
                }
            }
            return[rx, (r ? r[1] : s)];
        };
    }, between:function (d1, p, d2) {
        d2 = d2 || d1;
        var _fn = _.each(_.ignore(d1), p, _.ignore(d2));
        return function (s) {
            var rx = _fn.call(this, s);
            return[
                [rx[0][0], r[0][2]],
                rx[1]
            ];
        };
    }, list:function (p, d, c) {
        d = d || _.rtoken(/^\s*/);
        c = c || null;
        return(p instanceof Array ? _.each(_.product(p.slice(0, -1), _.ignore(d)), p.slice(-1), _.ignore(c)) : _.each(_.many(_.each(p, _.ignore(d))), px, _.ignore(c)));
    }, set:function (px, d, c) {
        d = d || _.rtoken(/^\s*/);
        c = c || null;
        return function (s) {
            var r = null, p = null, q = null, rx = null, best = [
                [],
                s
            ], last = false;
            for (var i = 0; i < px.length; i++) {
                q = null;
                p = null;
                r = null;
                last = (px.length == 1);
                try {
                    r = px[i].call(this, s);
                } catch (e) {
                    continue;
                }
                rx = [
                    [r[0]],
                    r[1]
                ];
                if (r[1].length > 0 && !last) {
                    try {
                        q = d.call(this, r[1]);
                    } catch (ex) {
                        last = true;
                    }
                } else {
                    last = true;
                }
                if (!last && q[1].length === 0) {
                    last = true;
                }
                if (!last) {
                    var qx = [];
                    for (var j = 0; j < px.length; j++) {
                        if (i != j) {
                            qx.push(px[j]);
                        }
                    }
                    p = _.set(qx, d).call(this, q[1]);
                    if (p[0].length > 0) {
                        rx[0] = rx[0].concat(p[0]);
                        rx[1] = p[1];
                    }
                }
                if (rx[1].length < best[1].length) {
                    best = rx;
                }
                if (best[1].length === 0) {
                    break;
                }
            }
            if (best[0].length === 0) {
                return best;
            }
            if (c) {
                try {
                    q = c.call(this, best[1]);
                } catch (ey) {
                    throw new $P.Exception(best[1]);
                }
                best[1] = q[1];
            }
            return best;
        };
    }, forward:function (gr, fname) {
        return function (s) {
            return gr[fname].call(this, s);
        };
    }, replace:function (rule, repl) {
        return function (s) {
            var r = rule.call(this, s);
            return[repl, r[1]];
        };
    }, process:function (rule, fn) {
        return function (s) {
            var r = rule.call(this, s);
            return[fn.call(this, r[0]), r[1]];
        };
    }, min:function (min, rule) {
        return function (s) {
            var rx = rule.call(this, s);
            if (rx[0].length < min) {
                throw new $P.Exception(s);
            }
            return rx;
        };
    }};
    var _generator = function (op) {
        return function () {
            var args = null, rx = [];
            if (arguments.length > 1) {
                args = Array.prototype.slice.call(arguments);
            } else if (arguments[0]instanceof Array) {
                args = arguments[0];
            }
            if (args) {
                for (var i = 0, px = args.shift(); i < px.length; i++) {
                    args.unshift(px[i]);
                    rx.push(op.apply(null, args));
                    args.shift();
                    return rx;
                }
            } else {
                return op.apply(null, arguments);
            }
        };
    };
    var gx = "optional not ignore cache".split(/\s/);
    for (var i = 0; i < gx.length; i++) {
        _[gx[i]] = _generator(_[gx[i]]);
    }
    var _vector = function (op) {
        return function () {
            if (arguments[0]instanceof Array) {
                return op.apply(null, arguments[0]);
            } else {
                return op.apply(null, arguments);
            }
        };
    };
    var vx = "each any all".split(/\s/);
    for (var j = 0; j < vx.length; j++) {
        _[vx[j]] = _vector(_[vx[j]]);
    }
}());
(function () {
    var flattenAndCompact = function (ax) {
        var rx = [];
        for (var i = 0; i < ax.length; i++) {
            if (ax[i]instanceof Array) {
                rx = rx.concat(flattenAndCompact(ax[i]));
            } else {
                if (ax[i]) {
                    rx.push(ax[i]);
                }
            }
        }
        return rx;
    };
    Date.Grammar = {};
    Date.Translator = {hour:function (s) {
        return function () {
            this.hour = Number(s);
        };
    }, minute:function (s) {
        return function () {
            this.minute = Number(s);
        };
    }, second:function (s) {
        return function () {
            this.second = Number(s);
        };
    }, meridian:function (s) {
        return function () {
            this.meridian = s.slice(0, 1).toLowerCase();
        };
    }, timezone:function (s) {
        return function () {
            var n = s.replace(/[^\d\+\-]/g, "");
            if (n.length) {
                this.timezoneOffset = Number(n);
            } else {
                this.timezone = s.toLowerCase();
            }
        };
    }, day:function (x) {
        var s = x[0];
        return function () {
            this.day = Number(s.match(/\d+/)[0]);
        };
    }, month:function (s) {
        return function () {
            this.month = ((s.length == 3) ? Date.getMonthNumberFromName(s) : (Number(s) - 1));
        };
    }, year:function (s) {
        return function () {
            var n = Number(s);
            this.year = ((s.length > 2) ? n : (n + (((n + 2000) < Date.CultureInfo.twoDigitYearMax) ? 2000 : 1900)));
        };
    }, rday:function (s) {
        return function () {
            switch (s) {
                case"yesterday":
                    this.days = -1;
                    break;
                case"tomorrow":
                    this.days = 1;
                    break;
                case"today":
                    this.days = 0;
                    break;
                case"now":
                    this.days = 0;
                    this.now = true;
                    break;
            }
        };
    }, finishExact:function (x) {
        x = (x instanceof Array) ? x : [x];
        var now = new Date();
        this.year = now.getFullYear();
        this.month = now.getMonth();
        this.day = 1;
        this.hour = 0;
        this.minute = 0;
        this.second = 0;
        for (var i = 0; i < x.length; i++) {
            if (x[i]) {
                x[i].call(this);
            }
        }
        this.hour = (this.meridian == "p" && this.hour < 13) ? this.hour + 12 : this.hour;
        if (this.day > Date.getDaysInMonth(this.year, this.month)) {
            throw new RangeError(this.day + " is not a valid value for days.");
        }
        var r = new Date(this.year, this.month, this.day, this.hour, this.minute, this.second);
        if (this.timezone) {
            r.set({timezone:this.timezone});
        } else if (this.timezoneOffset) {
            r.set({timezoneOffset:this.timezoneOffset});
        }
        return r;
    }, finish:function (x) {
        x = (x instanceof Array) ? flattenAndCompact(x) : [x];
        if (x.length === 0) {
            return null;
        }
        for (var i = 0; i < x.length; i++) {
            if (typeof x[i] == "function") {
                x[i].call(this);
            }
        }
        if (this.now) {
            return new Date();
        }
        var today = Date.today();
        var method = null;
        var expression = !!(this.days != null || this.orient || this.operator);
        if (expression) {
            var gap, mod, orient;
            orient = ((this.orient == "past" || this.operator == "subtract") ? -1 : 1);
            if (this.weekday) {
                this.unit = "day";
                gap = (Date.getDayNumberFromName(this.weekday) - today.getDay());
                mod = 7;
                this.days = gap ? ((gap + (orient * mod)) % mod) : (orient * mod);
            }
            if (this.month) {
                this.unit = "month";
                gap = (this.month - today.getMonth());
                mod = 12;
                this.months = gap ? ((gap + (orient * mod)) % mod) : (orient * mod);
                this.month = null;
            }
            if (!this.unit) {
                this.unit = "day";
            }
            if (this[this.unit + "s"] == null || this.operator != null) {
                if (!this.value) {
                    this.value = 1;
                }
                if (this.unit == "week") {
                    this.unit = "day";
                    this.value = this.value * 7;
                }
                this[this.unit + "s"] = this.value * orient;
            }
            return today.add(this);
        } else {
            if (this.meridian && this.hour) {
                this.hour = (this.hour < 13 && this.meridian == "p") ? this.hour + 12 : this.hour;
            }
            if (this.weekday && !this.day) {
                this.day = (today.addDays((Date.getDayNumberFromName(this.weekday) - today.getDay()))).getDate();
            }
            if (this.month && !this.day) {
                this.day = 1;
            }
            return today.set(this);
        }
    }};
    var _ = Date.Parsing.Operators, g = Date.Grammar, t = Date.Translator, _fn;
    g.datePartDelimiter = _.rtoken(/^([\s\-\.\,\/\x27]+)/);
    g.timePartDelimiter = _.stoken(":");
    g.whiteSpace = _.rtoken(/^\s*/);
    g.generalDelimiter = _.rtoken(/^(([\s\,]|at|on)+)/);
    var _C = {};
    g.ctoken = function (keys) {
        var fn = _C[keys];
        if (!fn) {
            var c = Date.CultureInfo.regexPatterns;
            var kx = keys.split(/\s+/), px = [];
            for (var i = 0; i < kx.length; i++) {
                px.push(_.replace(_.rtoken(c[kx[i]]), kx[i]));
            }
            fn = _C[keys] = _.any.apply(null, px);
        }
        return fn;
    };
    g.ctoken2 = function (key) {
        return _.rtoken(Date.CultureInfo.regexPatterns[key]);
    };
    g.h = _.cache(_.process(_.rtoken(/^(0[0-9]|1[0-2]|[1-9])/), t.hour));
    g.hh = _.cache(_.process(_.rtoken(/^(0[0-9]|1[0-2])/), t.hour));
    g.H = _.cache(_.process(_.rtoken(/^([0-1][0-9]|2[0-3]|[0-9])/), t.hour));
    g.HH = _.cache(_.process(_.rtoken(/^([0-1][0-9]|2[0-3])/), t.hour));
    g.m = _.cache(_.process(_.rtoken(/^([0-5][0-9]|[0-9])/), t.minute));
    g.mm = _.cache(_.process(_.rtoken(/^[0-5][0-9]/), t.minute));
    g.s = _.cache(_.process(_.rtoken(/^([0-5][0-9]|[0-9])/), t.second));
    g.ss = _.cache(_.process(_.rtoken(/^[0-5][0-9]/), t.second));
    g.hms = _.cache(_.sequence([g.H, g.mm, g.ss], g.timePartDelimiter));
    g.t = _.cache(_.process(g.ctoken2("shortMeridian"), t.meridian));
    g.tt = _.cache(_.process(g.ctoken2("longMeridian"), t.meridian));
    g.z = _.cache(_.process(_.rtoken(/^(\+|\-)?\s*\d\d\d\d?/), t.timezone));
    g.zz = _.cache(_.process(_.rtoken(/^(\+|\-)\s*\d\d\d\d/), t.timezone));
    g.zzz = _.cache(_.process(g.ctoken2("timezone"), t.timezone));
    g.timeSuffix = _.each(_.ignore(g.whiteSpace), _.set([g.tt, g.zzz]));
    g.time = _.each(_.optional(_.ignore(_.stoken("T"))), g.hms, g.timeSuffix);
    g.d = _.cache(_.process(_.each(_.rtoken(/^([0-2]\d|3[0-1]|\d)/), _.optional(g.ctoken2("ordinalSuffix"))), t.day));
    g.dd = _.cache(_.process(_.each(_.rtoken(/^([0-2]\d|3[0-1])/), _.optional(g.ctoken2("ordinalSuffix"))), t.day));
    g.ddd = g.dddd = _.cache(_.process(g.ctoken("sun mon tue wed thu fri sat"), function (s) {
        return function () {
            this.weekday = s;
        };
    }));
    g.M = _.cache(_.process(_.rtoken(/^(1[0-2]|0\d|\d)/), t.month));
    g.MM = _.cache(_.process(_.rtoken(/^(1[0-2]|0\d)/), t.month));
    g.MMM = g.MMMM = _.cache(_.process(g.ctoken("jan feb mar apr may jun jul aug sep oct nov dec"), t.month));
    g.y = _.cache(_.process(_.rtoken(/^(\d\d?)/), t.year));
    g.yy = _.cache(_.process(_.rtoken(/^(\d\d)/), t.year));
    g.yyy = _.cache(_.process(_.rtoken(/^(\d\d?\d?\d?)/), t.year));
    g.yyyy = _.cache(_.process(_.rtoken(/^(\d\d\d\d)/), t.year));
    _fn = function () {
        return _.each(_.any.apply(null, arguments), _.not(g.ctoken2("timeContext")));
    };
    g.day = _fn(g.d, g.dd);
    g.month = _fn(g.M, g.MMM);
    g.year = _fn(g.yyyy, g.yy);
    g.orientation = _.process(g.ctoken("past future"), function (s) {
        return function () {
            this.orient = s;
        };
    });
    g.operator = _.process(g.ctoken("add subtract"), function (s) {
        return function () {
            this.operator = s;
        };
    });
    g.rday = _.process(g.ctoken("yesterday tomorrow today now"), t.rday);
    g.unit = _.process(g.ctoken("minute hour day week month year"), function (s) {
        return function () {
            this.unit = s;
        };
    });
    g.value = _.process(_.rtoken(/^\d\d?(st|nd|rd|th)?/), function (s) {
        return function () {
            this.value = s.replace(/\D/g, "");
        };
    });
    g.expression = _.set([g.rday, g.operator, g.value, g.unit, g.orientation, g.ddd, g.MMM]);
    _fn = function () {
        return _.set(arguments, g.datePartDelimiter);
    };
    g.mdy = _fn(g.ddd, g.month, g.day, g.year);
    g.ymd = _fn(g.ddd, g.year, g.month, g.day);
    g.dmy = _fn(g.ddd, g.day, g.month, g.year);
    g.date = function (s) {
        return((g[Date.CultureInfo.dateElementOrder] || g.mdy).call(this, s));
    };
    g.format = _.process(_.many(_.any(_.process(_.rtoken(/^(dd?d?d?|MM?M?M?|yy?y?y?|hh?|HH?|mm?|ss?|tt?|zz?z?)/), function (fmt) {
        if (g[fmt]) {
            return g[fmt];
        } else {
            throw Date.Parsing.Exception(fmt);
        }
    }), _.process(_.rtoken(/^[^dMyhHmstz]+/), function (s) {
        return _.ignore(_.stoken(s));
    }))), function (rules) {
        return _.process(_.each.apply(null, rules), t.finishExact);
    });
    var _F = {};
    var _get = function (f) {
        return _F[f] = (_F[f] || g.format(f)[0]);
    };
    g.formats = function (fx) {
        if (fx instanceof Array) {
            var rx = [];
            for (var i = 0; i < fx.length; i++) {
                rx.push(_get(fx[i]));
            }
            return _.any.apply(null, rx);
        } else {
            return _get(fx);
        }
    };
    g._formats = g.formats(["yyyy-MM-ddTHH:mm:ss", "ddd, MMM dd, yyyy H:mm:ss tt", "ddd MMM d yyyy HH:mm:ss zzz", "d"]);
    g._start = _.process(_.set([g.date, g.time, g.expression], g.generalDelimiter, g.whiteSpace), t.finish);
    g.start = function (s) {
        try {
            var r = g._formats.call({}, s);
            if (r[1].length === 0) {
                return r;
            }
        } catch (e) {
        }
        return g._start.call({}, s);
    };
}());
Date._parse = Date.parse;
Date.parse = function (s) {
    var r = null;
    if (!s) {
        return null;
    }
    try {
        r = Date.Grammar.start.call({}, s);
    } catch (e) {
        return null;
    }
    return((r[1].length === 0) ? r[0] : null);
};
Date.getParseFunction = function (fx) {
    var fn = Date.Grammar.formats(fx);
    return function (s) {
        var r = null;
        try {
            r = fn.call({}, s);
        } catch (e) {
            return null;
        }
        return((r[1].length === 0) ? r[0] : null);
    };
};
Date.parseExact = function (s, fx) {
    return Date.getParseFunction(fx)(s);
};


/**
 * @version: 1.0 Alpha-1
 * @author: Coolite Inc. http://www.coolite.com/
 * @date: 2008-04-13
 * @copyright: Copyright (c) 2006-2008, Coolite Inc. (http://www.coolite.com/). All rights reserved.
 * @license: Licensed under The MIT License. See license.txt and http://www.datejs.com/license/.
 * @website: http://www.datejs.com/
 */

/*
 * TimeSpan(milliseconds);
 * TimeSpan(days, hours, minutes, seconds);
 * TimeSpan(days, hours, minutes, seconds, milliseconds);
 */
var TimeSpan = function (days, hours, minutes, seconds, milliseconds) {
    var attrs = "days hours minutes seconds milliseconds".split(/\s+/);

    var gFn = function (attr) {
        return function () {
            return this[attr];
        };
    };

    var sFn = function (attr) {
        return function (val) {
            this[attr] = val;
            return this;
        };
    };

    for (var i = 0; i < attrs.length; i++) {
        var $a = attrs[i], $b = $a.slice(0, 1).toUpperCase() + $a.slice(1);
        TimeSpan.prototype[$a] = 0;
        TimeSpan.prototype["get" + $b] = gFn($a);
        TimeSpan.prototype["set" + $b] = sFn($a);
    }

    if (arguments.length == 4) {
        this.setDays(days);
        this.setHours(hours);
        this.setMinutes(minutes);
        this.setSeconds(seconds);
    } else if (arguments.length == 5) {
        this.setDays(days);
        this.setHours(hours);
        this.setMinutes(minutes);
        this.setSeconds(seconds);
        this.setMilliseconds(milliseconds);
    } else if (arguments.length == 1 && typeof days == "number") {
        var orient = (days < 0) ? -1 : +1;
        this.setMilliseconds(Math.abs(days));

        this.setDays(Math.floor(this.getMilliseconds() / 86400000) * orient);
        this.setMilliseconds(this.getMilliseconds() % 86400000);

        this.setHours(Math.floor(this.getMilliseconds() / 3600000) * orient);
        this.setMilliseconds(this.getMilliseconds() % 3600000);

        this.setMinutes(Math.floor(this.getMilliseconds() / 60000) * orient);
        this.setMilliseconds(this.getMilliseconds() % 60000);

        this.setSeconds(Math.floor(this.getMilliseconds() / 1000) * orient);
        this.setMilliseconds(this.getMilliseconds() % 1000);

        this.setMilliseconds(this.getMilliseconds() * orient);
    }

    this.getTotalMilliseconds = function () {
        return (this.getDays() * 86400000) + (this.getHours() * 3600000) + (this.getMinutes() * 60000) + (this.getSeconds() * 1000);
    };

    this.compareTo = function (time) {
        var t1 = new Date(1970, 1, 1, this.getHours(), this.getMinutes(), this.getSeconds()), t2;
        if (time === null) {
            t2 = new Date(1970, 1, 1, 0, 0, 0);
        }
        else {
            t2 = new Date(1970, 1, 1, time.getHours(), time.getMinutes(), time.getSeconds());
        }
        return (t1 < t2) ? -1 : (t1 > t2) ? 1 : 0;
    };

    this.equals = function (time) {
        return (this.compareTo(time) === 0);
    };

    this.add = function (time) {
        return (time === null) ? this : this.addSeconds(time.getTotalMilliseconds() / 1000);
    };

    this.subtract = function (time) {
        return (time === null) ? this : this.addSeconds(-time.getTotalMilliseconds() / 1000);
    };

    this.addDays = function (n) {
        return new TimeSpan(this.getTotalMilliseconds() + (n * 86400000));
    };

    this.addHours = function (n) {
        return new TimeSpan(this.getTotalMilliseconds() + (n * 3600000));
    };

    this.addMinutes = function (n) {
        return new TimeSpan(this.getTotalMilliseconds() + (n * 60000));
    };

    this.addSeconds = function (n) {
        return new TimeSpan(this.getTotalMilliseconds() + (n * 1000));
    };

    this.addMilliseconds = function (n) {
        return new TimeSpan(this.getTotalMilliseconds() + n);
    };

    this.get12HourHour = function () {
        return (this.getHours() > 12) ? this.getHours() - 12 : (this.getHours() === 0) ? 12 : this.getHours();
    };

    this.getDesignator = function () {
        return (this.getHours() < 12) ? Date.CultureInfo.amDesignator : Date.CultureInfo.pmDesignator;
    };

    this.toString = function (format) {
        this._toString = function () {
            if (this.getDays() !== null && this.getDays() > 0) {
                return this.getDays() + "." + this.getHours() + ":" + this.p(this.getMinutes()) + ":" + this.p(this.getSeconds());
            }
            else {
                return this.getHours() + ":" + this.p(this.getMinutes()) + ":" + this.p(this.getSeconds());
            }
        };

        this.p = function (s) {
            return (s.toString().length < 2) ? "0" + s : s;
        };

        var me = this;

        return format ? format.replace(/dd?|HH?|hh?|mm?|ss?|tt?/g,
            function (format) {
                switch (format) {
                    case "d":
                        return me.getDays();
                    case "dd":
                        return me.p(me.getDays());
                    case "H":
                        return me.getHours();
                    case "HH":
                        return me.p(me.getHours());
                    case "h":
                        return me.get12HourHour();
                    case "hh":
                        return me.p(me.get12HourHour());
                    case "m":
                        return me.getMinutes();
                    case "mm":
                        return me.p(me.getMinutes());
                    case "s":
                        return me.getSeconds();
                    case "ss":
                        return me.p(me.getSeconds());
                    case "t":
                        return ((me.getHours() < 12) ? Date.CultureInfo.amDesignator : Date.CultureInfo.pmDesignator).substring(0, 1);
                    case "tt":
                        return (me.getHours() < 12) ? Date.CultureInfo.amDesignator : Date.CultureInfo.pmDesignator;
                }
            }
        ) : this._toString();
    };
    return this;
};

/**
 * Gets the time of day for this date instances.
 * @return {TimeSpan} TimeSpan
 */
Date.prototype.getTimeOfDay = function () {
    return new TimeSpan(0, this.getHours(), this.getMinutes(), this.getSeconds(), this.getMilliseconds());
};

/*
 * TimePeriod(startDate, endDate);
 * TimePeriod(years, months, days, hours, minutes, seconds, milliseconds);
 */
var TimePeriod = function (years, months, days, hours, minutes, seconds, milliseconds) {
    var attrs = "years months days hours minutes seconds milliseconds".split(/\s+/);

    var gFn = function (attr) {
        return function () {
            return this[attr];
        };
    };

    var sFn = function (attr) {
        return function (val) {
            this[attr] = val;
            return this;
        };
    };

    for (var i = 0; i < attrs.length; i++) {
        var $a = attrs[i], $b = $a.slice(0, 1).toUpperCase() + $a.slice(1);
        TimePeriod.prototype[$a] = 0;
        TimePeriod.prototype["get" + $b] = gFn($a);
        TimePeriod.prototype["set" + $b] = sFn($a);
    }

    if (arguments.length == 7) {
        this.years = years;
        this.months = months;
        this.setDays(days);
        this.setHours(hours);
        this.setMinutes(minutes);
        this.setSeconds(seconds);
        this.setMilliseconds(milliseconds);
    } else if (arguments.length == 2 && arguments[0] instanceof Date && arguments[1] instanceof Date) {
        // startDate and endDate as arguments

        var d1 = years.clone();
        var d2 = months.clone();

        var temp = d1.clone();
        var orient = (d1 > d2) ? -1 : +1;

        this.years = d2.getFullYear() - d1.getFullYear();
        temp.addYears(this.years);

        if (orient == +1) {
            if (temp > d2) {
                if (this.years !== 0) {
                    this.years--;
                }
            }
        } else {
            if (temp < d2) {
                if (this.years !== 0) {
                    this.years++;
                }
            }
        }

        d1.addYears(this.years);

        if (orient == +1) {
            while (d1 < d2 && d1.clone().addDays(Date.getDaysInMonth(d1.getYear(), d1.getMonth())) < d2) {
                d1.addMonths(1);
                this.months++;
            }
        }
        else {
            while (d1 > d2 && d1.clone().addDays(-d1.getDaysInMonth()) > d2) {
                d1.addMonths(-1);
                this.months--;
            }
        }

        var diff = d2 - d1;

        if (diff !== 0) {
            var ts = new TimeSpan(diff);
            this.setDays(ts.getDays());
            this.setHours(ts.getHours());
            this.setMinutes(ts.getMinutes());
            this.setSeconds(ts.getSeconds());
            this.setMilliseconds(ts.getMilliseconds());
        }
    }
    return this;
};

/**
 * --------------------------------------------------------------------
 * jQuery-Plugin "daterangepicker.jQuery.js"
 * by Scott Jehl, scott@filamentgroup.com
 * reference article: http://www.filamentgroup.com/lab/update_date_range_picker_with_jquery_ui/
 * demo page: http://www.filamentgroup.com/examples/daterangepicker/
 *
 * Copyright (c) 2010 Filament Group, Inc
 * Dual licensed under the MIT (filamentgroup.com/examples/mit-license.txt) and GPL (filamentgroup.com/examples/gpl-license.txt) licenses.
 *
 * Dependencies: jquery, jquery UI datepicker, date.js, jQuery UI CSS Framework

 *  12.15.2010 Made some fixes to resolve breaking changes introduced by jQuery UI 1.8.7
 * --------------------------------------------------------------------
 */

(function (jQuery) {


    jQuery.fn.daterangepicker = function (settings) {
        var rangeInput = jQuery(this);

        //defaults
        var options = jQuery.extend({
            presetRanges:[
                {text:'Today', dateStart:'today', dateEnd:'today' },
                {text:'Last 7 days', dateStart:'today-7days', dateEnd:'today' },
                {text:'Month to date', dateStart:function () {
                    return Date.parse('today').moveToFirstDayOfMonth();
                }, dateEnd:'today' },
                {text:'Year to date', dateStart:function () {
                    var x = Date.parse('today');
                    x.setMonth(0);
                    x.setDate(1);
                    return x;
                }, dateEnd:'today' },
                //extras:
                {text:'The previous Month', dateStart:function () {
                    return Date.parse('1 month ago').moveToFirstDayOfMonth();
                }, dateEnd:function () {
                    return Date.parse('1 month ago').moveToLastDayOfMonth();
                } }
                //{text: 'Tomorrow', dateStart: 'Tomorrow', dateEnd: 'Tomorrow' },
                //{text: 'Ad Campaign', dateStart: '03/07/08', dateEnd: 'Today' },
                //{text: 'Last 30 Days', dateStart: 'Today-30', dateEnd: 'Today' },
                //{text: 'Next 30 Days', dateStart: 'Today', dateEnd: 'Today+30' },
                //{text: 'Our Ad Campaign', dateStart: '03/07/08', dateEnd: '07/08/08' }
            ],
            //presetRanges: array of objects for each menu preset.
            //Each obj must have text, dateStart, dateEnd. dateStart, dateEnd accept date.js string or a function which returns a date object
            presets:{
                specificDate:'Specific Date',
                allDatesBefore:'All Dates Before',
                allDatesAfter:'All Dates After',
                dateRange:'Date Range'
            },
            rangeStartTitle:'Start date',
            rangeEndTitle:'End date',
            nextLinkText:$.datepicker._defaults.nextText,
            prevLinkText:$.datepicker._defaults.prevText,
            doneButtonText:'Done',
            earliestDate:Date.parse('-15years'), //earliest date allowed
            latestDate:Date.parse('today'), //latest date allowed
            constrainDates:false,
            rangeSplitter:'-', //string to use between dates in single input
            dateFormat:$.datepicker._defaults.dateFormat, // date formatting. Available formats: http://docs.jquery.com/UI/Datepicker/%24.datepicker.formatDate
            closeOnSelect:true, //if a complete selection is made, close the menu
            arrows:false,
            appendTo:'body',
            onClose:function () {
            },
            onOpen:function () {
            },
            onChange:function () {
            },
            datepickerOptions:null //object containing native UI datepicker API options
        }, settings);


        //custom datepicker options, extended by options
        var datepickerOptions = {
            onSelect:function (dateText, inst) {
                if (rp.find('.ui-daterangepicker-specificDate').is('.ui-state-active')) {
                    rp.find('.range-end').datepicker('setDate', rp.find('.range-start').datepicker('getDate'));
                }

                $(this).trigger('constrainOtherPicker');

                var rangeA = fDate(rp.find('.range-start').datepicker('getDate'));
                var rangeB = fDate(rp.find('.range-end').datepicker('getDate'));

                //send back to input or inputs
                if (rangeInput.length == 2) {
                    rangeInput.eq(0).val(rangeA);
                    rangeInput.eq(1).val(rangeB);
                }
                else {
                    rangeInput.val((rangeA != rangeB) ? rangeA + ' ' + options.rangeSplitter + ' ' + rangeB : rangeA);
                }
                //if closeOnSelect is true
                if (options.closeOnSelect) {
                    if (!rp.find('li.ui-state-active').is('.ui-daterangepicker-dateRange') && !rp.is(':animated')) {
                        hideRP();
                    }
                }
                options.onChange();
            },
            defaultDate:+0
        };

        //change event fires both when a calendar is updated or a change event on the input is triggered
        rangeInput.bind('change', options.onChange);

        //datepicker options from options
        options.datepickerOptions = (settings) ? jQuery.extend(datepickerOptions, settings.datepickerOptions) : datepickerOptions;

        //Capture Dates from input(s)
        var inputDateA, inputDateB = Date.parse('today');
        var inputDateAtemp, inputDateBtemp;
        if (rangeInput.size() == 2) {
            inputDateAtemp = Date.parse(rangeInput.eq(0).val());
            inputDateBtemp = Date.parse(rangeInput.eq(1).val());
            if (inputDateAtemp == null) {
                inputDateAtemp = inputDateBtemp;
            }
            if (inputDateBtemp == null) {
                inputDateBtemp = inputDateAtemp;
            }
        }
        else {
            inputDateAtemp = Date.parse(rangeInput.val().split(options.rangeSplitter)[0]);
            inputDateBtemp = Date.parse(rangeInput.val().split(options.rangeSplitter)[1]);
            if (inputDateBtemp == null) {
                inputDateBtemp = inputDateAtemp;
            } //if one date, set both
        }
        if (inputDateAtemp != null) {
            inputDateA = inputDateAtemp;
        }
        if (inputDateBtemp != null) {
            inputDateB = inputDateBtemp;
        }


        //build picker and
        var rp = jQuery('<div class="ui-daterangepicker ui-widget ui-helper-clearfix ui-widget-content ui-corner-all"></div>');
        var rpPresets = (function () {
            var ul = jQuery('<ul class="ui-widget-content"></ul>').appendTo(rp);
            jQuery.each(options.presetRanges, function () {
                jQuery('<li class="ui-daterangepicker-' + this.text.replace(/ /g, '') + ' ui-corner-all"><a href="#">' + this.text + '</a></li>')
                    .data('dateStart', this.dateStart)
                    .data('dateEnd', this.dateEnd)
                    .appendTo(ul);
            });
            var x = 0;
            jQuery.each(options.presets, function (key, value) {
                jQuery('<li class="ui-daterangepicker-' + key + ' preset_' + x + ' ui-helper-clearfix ui-corner-all"><span class="ui-icon ui-icon-triangle-1-e"></span><a href="#">' + value + '</a></li>')
                    .appendTo(ul);
                x++;
            });

            ul.find('li').hover(
                function () {
                    jQuery(this).addClass('ui-state-hover');
                },
                function () {
                    jQuery(this).removeClass('ui-state-hover');
                })
                .click(function () {
                    rp.find('.ui-state-active').removeClass('ui-state-active');
                    jQuery(this).addClass('ui-state-active');
                    clickActions(jQuery(this), rp, rpPickers, doneBtn);
                    return false;
                });
            return ul;
        })();

        //function to format a date string
        function fDate(date) {
            if (!date.getDate()) {
                return '';
            }
            var day = date.getDate();
            var month = date.getMonth();
            var year = date.getFullYear();
            month++; // adjust javascript month
            var dateFormat = options.dateFormat;
            return jQuery.datepicker.formatDate(dateFormat, date);
        }


        jQuery.fn.restoreDateFromData = function () {
            if (jQuery(this).data('saveDate')) {
                jQuery(this).datepicker('setDate', jQuery(this).data('saveDate')).removeData('saveDate');
            }
            return this;
        }
        jQuery.fn.saveDateToData = function () {
            if (!jQuery(this).data('saveDate')) {
                jQuery(this).data('saveDate', jQuery(this).datepicker('getDate'));
            }
            return this;
        }

        //show, hide, or toggle rangepicker
        function showRP() {
            if (rp.data('state') == 'closed') {
                positionRP();
                rp.fadeIn(300).data('state', 'open');
                options.onOpen();
            }
        }

        function hideRP() {
            if (rp.data('state') == 'open') {
                rp.fadeOut(300).data('state', 'closed');
                options.onClose();
            }
        }

        function toggleRP() {
            if (rp.data('state') == 'open') {
                hideRP();
            }
            else {
                showRP();
            }
        }

        function positionRP() {
            var relEl = riContain || rangeInput; //if arrows, use parent for offsets
            var riOffset = relEl.offset(),
                side = 'left',
                val = riOffset.left,
                offRight = jQuery(window).width() - val - relEl.outerWidth();

            if (val > offRight) {
                side = 'right', val = offRight;
            }

            rp.parent().css(side, val).css('top', riOffset.top + relEl.outerHeight());
        }


        //preset menu click events
        function clickActions(el, rp, rpPickers, doneBtn) {

            if (el.is('.ui-daterangepicker-specificDate')) {
                //Specific Date (show the "start" calendar)
                doneBtn.hide();
                rpPickers.show();
                rp.find('.title-start').text(options.presets.specificDate);
                rp.find('.range-start').restoreDateFromData().css('opacity', 1).show(400);
                rp.find('.range-end').restoreDateFromData().css('opacity', 0).hide(400);
                setTimeout(function () {
                    doneBtn.fadeIn();
                }, 400);
            }
            else if (el.is('.ui-daterangepicker-allDatesBefore')) {
                //All dates before specific date (show the "end" calendar and set the "start" calendar to the earliest date)
                doneBtn.hide();
                rpPickers.show();
                rp.find('.title-end').text(options.presets.allDatesBefore);
                rp.find('.range-start').saveDateToData().datepicker('setDate', options.earliestDate).css('opacity', 0).hide(400);
                rp.find('.range-end').restoreDateFromData().css('opacity', 1).show(400);
                setTimeout(function () {
                    doneBtn.fadeIn();
                }, 400);
            }
            else if (el.is('.ui-daterangepicker-allDatesAfter')) {
                //All dates after specific date (show the "start" calendar and set the "end" calendar to the latest date)
                doneBtn.hide();
                rpPickers.show();
                rp.find('.title-start').text(options.presets.allDatesAfter);
                rp.find('.range-start').restoreDateFromData().css('opacity', 1).show(400);
                rp.find('.range-end').saveDateToData().datepicker('setDate', options.latestDate).css('opacity', 0).hide(400);
                setTimeout(function () {
                    doneBtn.fadeIn();
                }, 400);
            }
            else if (el.is('.ui-daterangepicker-dateRange')) {
                //Specific Date range (show both calendars)
                doneBtn.hide();
                rpPickers.show();
                rp.find('.title-start').text(options.rangeStartTitle);
                rp.find('.title-end').text(options.rangeEndTitle);
                rp.find('.range-start').restoreDateFromData().css('opacity', 1).show(400);
                rp.find('.range-end').restoreDateFromData().css('opacity', 1).show(400);
                setTimeout(function () {
                    doneBtn.fadeIn();
                }, 400);
            }
            else {
                //custom date range specified in the options (no calendars shown)
                doneBtn.hide();
                rp.find('.range-start, .range-end').css('opacity', 0).hide(400, function () {
                    rpPickers.hide();
                });
                var dateStart = (typeof el.data('dateStart') == 'string') ? Date.parse(el.data('dateStart')) : el.data('dateStart')();
                var dateEnd = (typeof el.data('dateEnd') == 'string') ? Date.parse(el.data('dateEnd')) : el.data('dateEnd')();
                rp.find('.range-start').datepicker('setDate', dateStart).find('.ui-datepicker-current-day').trigger('click');
                rp.find('.range-end').datepicker('setDate', dateEnd).find('.ui-datepicker-current-day').trigger('click');
            }

            return false;
        }


        //picker divs
        var rpPickers = jQuery('<div class="ranges ui-widget-header ui-corner-all ui-helper-clearfix"><div class="range-start"><span class="title-start">Start Date</span></div><div class="range-end"><span class="title-end">End Date</span></div></div>').appendTo(rp);
        rpPickers.find('.range-start, .range-end')
            .datepicker(options.datepickerOptions);


        rpPickers.find('.range-start').datepicker('setDate', inputDateA);
        rpPickers.find('.range-end').datepicker('setDate', inputDateB);

        rpPickers.find('.range-start, .range-end')
            .bind('constrainOtherPicker', function () {
                if (options.constrainDates) {
                    //constrain dates
                    if ($(this).is('.range-start')) {
                        rp.find('.range-end').datepicker("option", "minDate", $(this).datepicker('getDate'));
                    }
                    else {
                        rp.find('.range-start').datepicker("option", "maxDate", $(this).datepicker('getDate'));
                    }
                }
            })
            .trigger('constrainOtherPicker');

        var doneBtn = jQuery('<button class="btnDone ui-state-default ui-corner-all">' + options.doneButtonText + '</button>')
            .click(function () {
                rp.find('.ui-datepicker-current-day').trigger('click');
                hideRP();
            })
            .hover(
            function () {
                jQuery(this).addClass('ui-state-hover');
            },
            function () {
                jQuery(this).removeClass('ui-state-hover');
            }
        )
            .appendTo(rpPickers);


        //inputs toggle rangepicker visibility
        jQuery(this).click(function () {
            toggleRP();
            return false;
        });
        //hide em all
        rpPickers.hide().find('.range-start, .range-end, .btnDone').hide();

        rp.data('state', 'closed');

        //Fixed for jQuery UI 1.8.7 - Calendars are hidden otherwise!
        rpPickers.find('.ui-datepicker').css("display", "block");

        //inject rp
        jQuery(options.appendTo).append(rp);

        //wrap and position
        rp.wrap('<div class="ui-daterangepickercontain"></div>');

        //add arrows (only available on one input)
        if (options.arrows && rangeInput.size() == 1) {
            var prevLink = jQuery('<a href="#" class="ui-daterangepicker-prev ui-corner-all" title="' + options.prevLinkText + '"><span class="ui-icon ui-icon-circle-triangle-w">' + options.prevLinkText + '</span></a>');
            var nextLink = jQuery('<a href="#" class="ui-daterangepicker-next ui-corner-all" title="' + options.nextLinkText + '"><span class="ui-icon ui-icon-circle-triangle-e">' + options.nextLinkText + '</span></a>');

            jQuery(this)
                .addClass('ui-rangepicker-input ui-widget-content')
                .wrap('<div class="ui-daterangepicker-arrows ui-widget ui-widget-header ui-helper-clearfix ui-corner-all"></div>')
                .before(prevLink)
                .before(nextLink)
                .parent().find('a').click(function () {
                    var dateA = rpPickers.find('.range-start').datepicker('getDate');
                    var dateB = rpPickers.find('.range-end').datepicker('getDate');
                    var diff = Math.abs(new TimeSpan(dateA - dateB).getTotalMilliseconds()) + 86400000; //difference plus one day
                    if (jQuery(this).is('.ui-daterangepicker-prev')) {
                        diff = -diff;
                    }

                    rpPickers.find('.range-start, .range-end ').each(function () {
                        var thisDate = jQuery(this).datepicker("getDate");
                        if (thisDate == null) {
                            return false;
                        }
                        jQuery(this).datepicker("setDate", thisDate.add({milliseconds:diff})).find('.ui-datepicker-current-day').trigger('click');
                    });
                    return false;
                })
                .hover(
                function () {
                    jQuery(this).addClass('ui-state-hover');
                },
                function () {
                    jQuery(this).removeClass('ui-state-hover');
                });

            var riContain = rangeInput.parent();
        }


        jQuery(document).click(function () {
            if (rp.is(':visible')) {
                hideRP();
            }
        });

        rp.click(function () {
            return false;
        }).hide();
        return this;
    }
})(jQuery);
// end of includes


(function ($, ko) {

    function strtodate(value) {
        var datetime = /([0-9]{4}-[0-9]{2}-[0-9]{2}) ?([0-9]{2}:[0-9]{2}:[0-9]{2})?/.exec(value);
        if(undefined !== datetime[1]) {
            if(undefined !== datetime[2]) {
                // datetime
                return new Date(datetime[1] + 'T' + datetime[2]);
            }
            else {
                // date only
                return new Date(datetime[1] + 'T00:00:00');
            }
        }
        else {
            // invalid date
            return false;
        }
    }

    ko.bindingHandlers.datepicker = {
        init:function (element, valueAccessor, allBindingsAccessor) {

            var $element = $(element);

            var rangePicker = allBindingsAccessor().rangePicker || false;

            var observable = valueAccessor(),
                value = ko.utils.unwrapObservable(observable),
                date;
            // set the default date

            if (typeof(value) == 'string') {
                date = strtodate(value);

            }
            else if(value instanceof Date) {
                date = value;
            }
            else {
                date = new Date();
                date.setHours(date.getHours() - date.getTimezoneOffset() / 60);
                observable(date);
            }

            //initialize datepicker with some optional options
            var options = allBindingsAccessor().datepickerOptions || {};

            options.defaultDate = date;

            if (rangePicker) {

                options.onChange = function () {
                        //$element.change();
                        var value = $element.val();

                        if (typeof(observable) == 'function') {
                            observable($element.val());
                        }

                        //console.log($element.data);
                    };

                $element.daterangepicker(options);
            }
            else {
                $element.datepicker(options);

                //handle the field changing
                // TODO verificar se value é Date ou String e configurar de acordo
                ko.utils.registerEventHandler(element, "change", function () {

                    if (typeof(observable) == 'function') {
                        if(observable() instanceof Date) {
                            observable($(element).datepicker("getDate"));
                        }
                        else {
                            observable($.datepicker.formatDate('yy-mm-dd', $(element).datepicker("getDate")));
                        }
                    }

                });

                //handle disposal (if KO removes by the template binding)
                ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                    $element.datepicker("destroy");
                });
            }


        },
        update:function (element, valueAccessor) {

            var value = ko.utils.unwrapObservable(valueAccessor());
            // TODO verificar se value é Date ou String e configurar de acordo

            if (undefined == value || typeof(value) == 'boolean') {
                return;
            }

            var date;

            if (typeof(value) == 'string') {
                date = strtodate(value);
            }
            else if(value instanceof Date) {
                date = value;
            }
            else {
                return;
            }


            $(element).datepicker("setDate", date);
        }
    };
})(jQuery, ko);
// - end of Datepicker
/*global jQuery:true, ko:true, elRTE:true */
// ElRTE / ElFinder
if (undefined !== window.elRTE) {
    (function ($, ko, elRTE) {
        "use strict";

        // From underscore, will debounce elrte updates on window.focus
        var limit = function (func, wait, debounce) {
            var timeout;
            return function () {
                var context = this, args = arguments;
                var throttler = function () {
                    timeout = null;
                    func.apply(context, args);
                };
                if (debounce) {
                    clearTimeout(timeout);
                }
                if (debounce || !timeout) {
                    timeout = setTimeout(throttler, wait);
                }
            };
        };

        ko.bindingHandlers.elrte = {
            init: function (element, valueAccessor, allBindingsAccessor) {
                var $element = $(element),
                    elrte = ko.utils.unwrapObservable(valueAccessor()),
                    value = allBindingsAccessor().value;

                if (value && value.subscribe) {
                    $element.val(ko.utils.unwrapObservable(value));

                    value.subscribe(function (newValue) {
                        if (!element._updating) {
                            $element.elrte('val', $element.val());
                        }
                    });
                }

                $element.elrte(elrte);

                // limit the update rate to every 200ms
                var updater = limit(function () {
                    element._updating = true;
                    //$element.val($element.elrte('val')).change();
                    $element.elrte('updateSource').change();
                    element._updating = false;
                }, 200, true);

                // elrte calls window.focus() when the ui is updated
                var _focus = element.elrte.iframe.contentWindow.window.focus;
                element.elrte.iframe.contentWindow.window.focus = function () {
                    updater();
                    _focus.apply(this, arguments);
                };

                // also update on editor keyups
                element.elrte.$doc.on('keyup', updater);


                ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                    $element.elrte('destroy');
                });
            },
            update: function (element, valueAccessor, allBindingsAccessor, context) {
                //handle programmatic updates to the observable
//            var options = ko.utils.unwrapObservable(valueAccessor());
                //$(element).fileupload('option', options);

            }
        };

        ko.bindingHandlers.elfinder = {
            init: function (element, valueAccessor) {
                var $element = $(element),
                    options = ko.utils.unwrapObservable(valueAccessor());

                $element.elfinder(options);

                ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                    $element.elfinder('destroy');
                });
            },
            update: function (element, valueAccessor, allBindingsAccessor, context) {
                //handle programmatic updates to the observable
//            var options = ko.utils.unwrapObservable(valueAccessor());
                //$(element).fileupload('option', options);

            }
        };
    })(jQuery, ko, elRTE);
}
// - end of ElRTE/ElFinder

// start fileupload
/*
 * jQuery Iframe Transport Plugin 1.4
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2011, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint unparam: true, nomen: true */
/*global define, window, document */

(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        // Register as an anonymous AMD module:
        define(['jquery'], factory);
    } else {
        // Browser globals:
        factory(window.jQuery);
    }
}(function ($) {
    'use strict';

    // Helper variable to create unique names for the transport iframes:
    var counter = 0;

    // The iframe transport accepts three additional options:
    // options.fileInput: a jQuery collection of file input fields
    // options.paramName: the parameter name for the file form data,
    //  overrides the name property of the file input field(s),
    //  can be a string or an array of strings.
    // options.formData: an array of objects with name and value properties,
    //  equivalent to the return data of .serializeArray(), e.g.:
    //  [{name: 'a', value: 1}, {name: 'b', value: 2}]
    $.ajaxTransport('iframe', function (options) {
        if (options.async && (options.type === 'POST' || options.type === 'GET')) {
            var form,
                iframe;
            return {
                send: function (_, completeCallback) {
                    form = $('<form style="display:none;"></form>');
                    // javascript:false as initial iframe src
                    // prevents warning popups on HTTPS in IE6.
                    // IE versions below IE8 cannot set the name property of
                    // elements that have already been added to the DOM,
                    // so we set the name along with the iframe HTML markup:
                    iframe = $(
                        '<iframe src="javascript:false;" name="iframe-transport-' +
                            (counter += 1) + '"></iframe>'
                    ).bind('load', function () {
                            var fileInputClones,
                                paramNames = $.isArray(options.paramName) ?
                                    options.paramName : [options.paramName];
                            iframe
                                .unbind('load')
                                .bind('load', function () {
                                    var response;
                                    // Wrap in a try/catch block to catch exceptions thrown
                                    // when trying to access cross-domain iframe contents:
                                    try {
                                        response = iframe.contents();
                                        // Google Chrome and Firefox do not throw an
                                        // exception when calling iframe.contents() on
                                        // cross-domain requests, so we unify the response:
                                        if (!response.length || !response[0].firstChild) {
                                            throw new Error();
                                        }
                                    } catch (e) {
                                        response = undefined;
                                    }
                                    // The complete callback returns the
                                    // iframe content document as response object:
                                    completeCallback(
                                        200,
                                        'success',
                                        {'iframe': response}
                                    );
                                    // Fix for IE endless progress bar activity bug
                                    // (happens on form submits to iframe targets):
                                    $('<iframe src="javascript:false;"></iframe>')
                                        .appendTo(form);
                                    form.remove();
                                });
                            form
                                .prop('target', iframe.prop('name'))
                                .prop('action', options.url)
                                .prop('method', options.type);
                            if (options.formData) {
                                $.each(options.formData, function (index, field) {
                                    $('<input type="hidden"/>')
                                        .prop('name', field.name)
                                        .val(field.value)
                                        .appendTo(form);
                                });
                            }
                            if (options.fileInput && options.fileInput.length &&
                                options.type === 'POST') {
                                fileInputClones = options.fileInput.clone();
                                // Insert a clone for each file input field:
                                options.fileInput.after(function (index) {
                                    return fileInputClones[index];
                                });
                                if (options.paramName) {
                                    options.fileInput.each(function (index) {
                                        $(this).prop(
                                            'name',
                                            paramNames[index] || options.paramName
                                        );
                                    });
                                }
                                // Appending the file input fields to the hidden form
                                // removes them from their original location:
                                form
                                    .append(options.fileInput)
                                    .prop('enctype', 'multipart/form-data')
                                    // enctype must be set as encoding for IE:
                                    .prop('encoding', 'multipart/form-data');
                            }
                            form.submit();
                            // Insert the file input fields at their original location
                            // by replacing the clones with the originals:
                            if (fileInputClones && fileInputClones.length) {
                                options.fileInput.each(function (index, input) {
                                    var clone = $(fileInputClones[index]);
                                    $(input).prop('name', clone.prop('name'));
                                    clone.replaceWith(input);
                                });
                            }
                        });
                    form.append(iframe).appendTo(document.body);
                },
                abort: function () {
                    if (iframe) {
                        // javascript:false as iframe src aborts the request
                        // and prevents warning popups on HTTPS in IE6.
                        // concat is used to avoid the "Script URL" JSLint error:
                        iframe
                            .unbind('load')
                            .prop('src', 'javascript'.concat(':false;'));
                    }
                    if (form) {
                        form.remove();
                    }
                }
            };
        }
    });

    // The iframe transport returns the iframe content document as response.
    // The following adds converters from iframe to text, json, html, and script:
    $.ajaxSetup({
        converters: {
            'iframe text': function (iframe) {
                return $(iframe[0].body).text();
            },
            'iframe json': function (iframe) {
                return $.parseJSON($(iframe[0].body).text());
            },
            'iframe html': function (iframe) {
                return $(iframe[0].body).html();
            },
            'iframe script': function (iframe) {
                return $.globalEval($(iframe[0].body).text());
            }
        }
    });

}));

/*
 * jQuery File Upload Plugin 5.10.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global define, window, document, Blob, FormData, location */

(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        // Register as an anonymous AMD module:
        define([
            'jquery',
            'jquery.ui.widget'
        ], factory);
    } else {
        // Browser globals:
        factory(window.jQuery);
    }
}(function ($) {
    'use strict';

    // The FileReader API is not actually used, but works as feature detection,
    // as e.g. Safari supports XHR file uploads via the FormData API,
    // but not non-multipart XHR file uploads:
    $.support.xhrFileUpload = !!(window.XMLHttpRequestUpload && window.FileReader);
    $.support.xhrFormDataFileUpload = !!window.FormData;

    // The fileupload widget listens for change events on file input fields defined
    // via fileInput setting and paste or drop events of the given dropZone.
    // In addition to the default jQuery Widget methods, the fileupload widget
    // exposes the "add" and "send" methods, to add or directly send files using
    // the fileupload API.
    // By default, files added via file input selection, paste, drag & drop or
    // "add" method are uploaded immediately, but it is possible to override
    // the "add" callback option to queue file uploads.
    $.widget('blueimp.fileupload', {

        options: {
            // The namespace used for event handler binding on the dropZone and
            // fileInput collections.
            // If not set, the name of the widget ("fileupload") is used.
            namespace: undefined,
            // The drop target collection, by the default the complete document.
            // Set to null or an empty collection to disable drag & drop support:
            dropZone: $(document),
            // The file input field collection, that is listened for change events.
            // If undefined, it is set to the file input fields inside
            // of the widget element on plugin initialization.
            // Set to null or an empty collection to disable the change listener.
            fileInput: undefined,
            // By default, the file input field is replaced with a clone after
            // each input field change event. This is required for iframe transport
            // queues and allows change events to be fired for the same file
            // selection, but can be disabled by setting the following option to false:
            replaceFileInput: true,
            // The parameter name for the file form data (the request argument name).
            // If undefined or empty, the name property of the file input field is
            // used, or "files[]" if the file input name property is also empty,
            // can be a string or an array of strings:
            paramName: undefined,
            // By default, each file of a selection is uploaded using an individual
            // request for XHR type uploads. Set to false to upload file
            // selections in one request each:
            singleFileUploads: true,
            // To limit the number of files uploaded with one XHR request,
            // set the following option to an integer greater than 0:
            limitMultiFileUploads: undefined,
            // Set the following option to true to issue all file upload requests
            // in a sequential order:
            sequentialUploads: false,
            // To limit the number of concurrent uploads,
            // set the following option to an integer greater than 0:
            limitConcurrentUploads: undefined,
            // Set the following option to true to force iframe transport uploads:
            forceIframeTransport: false,
            // Set the following option to the location of a redirect url on the
            // origin server, for cross-domain iframe transport uploads:
            redirect: undefined,
            // The parameter name for the redirect url, sent as part of the form
            // data and set to 'redirect' if this option is empty:
            redirectParamName: undefined,
            // Set the following option to the location of a postMessage window,
            // to enable postMessage transport uploads:
            postMessage: undefined,
            // By default, XHR file uploads are sent as multipart/form-data.
            // The iframe transport is always using multipart/form-data.
            // Set to false to enable non-multipart XHR uploads:
            multipart: true,
            // To upload large files in smaller chunks, set the following option
            // to a preferred maximum chunk size. If set to 0, null or undefined,
            // or the browser does not support the required Blob API, files will
            // be uploaded as a whole.
            maxChunkSize: undefined,
            // When a non-multipart upload or a chunked multipart upload has been
            // aborted, this option can be used to resume the upload by setting
            // it to the size of the already uploaded bytes. This option is most
            // useful when modifying the options object inside of the "add" or
            // "send" callbacks, as the options are cloned for each file upload.
            uploadedBytes: undefined,
            // By default, failed (abort or error) file uploads are removed from the
            // global progress calculation. Set the following option to false to
            // prevent recalculating the global progress data:
            recalculateProgress: true,

            // Additional form data to be sent along with the file uploads can be set
            // using this option, which accepts an array of objects with name and
            // value properties, a function returning such an array, a FormData
            // object (for XHR file uploads), or a simple object.
            // The form of the first fileInput is given as parameter to the function:
            formData: function (form) {
                return form.serializeArray();
            },

            // The add callback is invoked as soon as files are added to the fileupload
            // widget (via file input selection, drag & drop, paste or add API call).
            // If the singleFileUploads option is enabled, this callback will be
            // called once for each file in the selection for XHR file uplaods, else
            // once for each file selection.
            // The upload starts when the submit method is invoked on the data parameter.
            // The data object contains a files property holding the added files
            // and allows to override plugin options as well as define ajax settings.
            // Listeners for this callback can also be bound the following way:
            // .bind('fileuploadadd', func);
            // data.submit() returns a Promise object and allows to attach additional
            // handlers using jQuery's Deferred callbacks:
            // data.submit().done(func).fail(func).always(func);
            add: function (e, data) {
                data.submit();
            },

            // Other callbacks:
            // Callback for the submit event of each file upload:
            // submit: function (e, data) {}, // .bind('fileuploadsubmit', func);
            // Callback for the start of each file upload request:
            // send: function (e, data) {}, // .bind('fileuploadsend', func);
            // Callback for successful uploads:
            // done: function (e, data) {}, // .bind('fileuploaddone', func);
            // Callback for failed (abort or error) uploads:
            // fail: function (e, data) {}, // .bind('fileuploadfail', func);
            // Callback for completed (success, abort or error) requests:
            // always: function (e, data) {}, // .bind('fileuploadalways', func);
            // Callback for upload progress events:
            // progress: function (e, data) {}, // .bind('fileuploadprogress', func);
            // Callback for global upload progress events:
            // progressall: function (e, data) {}, // .bind('fileuploadprogressall', func);
            // Callback for uploads start, equivalent to the global ajaxStart event:
            // start: function (e) {}, // .bind('fileuploadstart', func);
            // Callback for uploads stop, equivalent to the global ajaxStop event:
            // stop: function (e) {}, // .bind('fileuploadstop', func);
            // Callback for change events of the fileInput collection:
            // change: function (e, data) {}, // .bind('fileuploadchange', func);
            // Callback for paste events to the dropZone collection:
            // paste: function (e, data) {}, // .bind('fileuploadpaste', func);
            // Callback for drop events of the dropZone collection:
            // drop: function (e, data) {}, // .bind('fileuploaddrop', func);
            // Callback for dragover events of the dropZone collection:
            // dragover: function (e) {}, // .bind('fileuploaddragover', func);

            // The plugin options are used as settings object for the ajax calls.
            // The following are jQuery ajax settings required for the file uploads:
            processData: false,
            contentType: false,
            cache: false
        },

        // A list of options that require a refresh after assigning a new value:
        _refreshOptionsList: [
            'namespace',
            'dropZone',
            'fileInput',
            'multipart',
            'forceIframeTransport'
        ],

        _isXHRUpload: function (options) {
            return !options.forceIframeTransport &&
                ((!options.multipart && $.support.xhrFileUpload) ||
                    $.support.xhrFormDataFileUpload);
        },

        _getFormData: function (options) {
            var formData;
            if (typeof options.formData === 'function') {
                return options.formData(options.form);
            } else if ($.isArray(options.formData)) {
                return options.formData;
            } else if (options.formData) {
                formData = [];
                $.each(options.formData, function (name, value) {
                    formData.push({name: name, value: value});
                });
                return formData;
            }
            return [];
        },

        _getTotal: function (files) {
            var total = 0;
            $.each(files, function (index, file) {
                total += file.size || 1;
            });
            return total;
        },

        _onProgress: function (e, data) {
            if (e.lengthComputable) {
                var total = data.total || this._getTotal(data.files),
                    loaded = parseInt(
                        e.loaded / e.total * (data.chunkSize || total),
                        10
                    ) + (data.uploadedBytes || 0);
                this._loaded += loaded - (data.loaded || data.uploadedBytes || 0);
                data.lengthComputable = true;
                data.loaded = loaded;
                data.total = total;
                // Trigger a custom progress event with a total data property set
                // to the file size(s) of the current upload and a loaded data
                // property calculated accordingly:
                this._trigger('progress', e, data);
                // Trigger a global progress event for all current file uploads,
                // including ajax calls queued for sequential file uploads:
                this._trigger('progressall', e, {
                    lengthComputable: true,
                    loaded: this._loaded,
                    total: this._total
                });
            }
        },

        _initProgressListener: function (options) {
            var that = this,
                xhr = options.xhr ? options.xhr() : $.ajaxSettings.xhr();
            // Accesss to the native XHR object is required to add event listeners
            // for the upload progress event:
            if (xhr.upload) {
                $(xhr.upload).bind('progress', function (e) {
                    var oe = e.originalEvent;
                    // Make sure the progress event properties get copied over:
                    e.lengthComputable = oe.lengthComputable;
                    e.loaded = oe.loaded;
                    e.total = oe.total;
                    that._onProgress(e, options);
                });
                options.xhr = function () {
                    return xhr;
                };
            }
        },

        _initXHRData: function (options) {
            var formData,
                file = options.files[0],
            // Ignore non-multipart setting if not supported:
                multipart = options.multipart || !$.support.xhrFileUpload,
                paramName = options.paramName[0];
            if (!multipart || options.blob) {
                // For non-multipart uploads and chunked uploads,
                // file meta data is not part of the request body,
                // so we transmit this data as part of the HTTP headers.
                // For cross domain requests, these headers must be allowed
                // via Access-Control-Allow-Headers or removed using
                // the beforeSend callback:
                options.headers = $.extend(options.headers, {
                    'X-File-Name': file.name,
                    'X-File-Type': file.type,
                    'X-File-Size': file.size
                });
                if (!options.blob) {
                    // Non-chunked non-multipart upload:
                    options.contentType = file.type;
                    options.data = file;
                } else if (!multipart) {
                    // Chunked non-multipart upload:
                    options.contentType = 'application/octet-stream';
                    options.data = options.blob;
                }
            }
            if (multipart && $.support.xhrFormDataFileUpload) {
                if (options.postMessage) {
                    // window.postMessage does not allow sending FormData
                    // objects, so we just add the File/Blob objects to
                    // the formData array and let the postMessage window
                    // create the FormData object out of this array:
                    formData = this._getFormData(options);
                    if (options.blob) {
                        formData.push({
                            name: paramName,
                            value: options.blob
                        });
                    } else {
                        $.each(options.files, function (index, file) {
                            formData.push({
                                name: options.paramName[index] || paramName,
                                value: file
                            });
                        });
                    }
                } else {
                    if (options.formData instanceof FormData) {
                        formData = options.formData;
                    } else {
                        formData = new FormData();
                        $.each(this._getFormData(options), function (index, field) {
                            formData.append(field.name, field.value);
                        });
                    }
                    if (options.blob) {
                        formData.append(paramName, options.blob, file.name);
                    } else {
                        $.each(options.files, function (index, file) {
                            // File objects are also Blob instances.
                            // This check allows the tests to run with
                            // dummy objects:
                            if (file instanceof Blob) {
                                formData.append(
                                    options.paramName[index] || paramName,
                                    file,
                                    file.name
                                );
                            }
                        });
                    }
                }
                options.data = formData;
            }
            // Blob reference is not needed anymore, free memory:
            options.blob = null;
        },

        _initIframeSettings: function (options) {
            // Setting the dataType to iframe enables the iframe transport:
            options.dataType = 'iframe ' + (options.dataType || '');
            // The iframe transport accepts a serialized array as form data:
            options.formData = this._getFormData(options);
            // Add redirect url to form data on cross-domain uploads:
            if (options.redirect && $('<a></a>').prop('href', options.url)
                .prop('host') !== location.host) {
                options.formData.push({
                    name: options.redirectParamName || 'redirect',
                    value: options.redirect
                });
            }
        },

        _initDataSettings: function (options) {
            if (this._isXHRUpload(options)) {
                if (!this._chunkedUpload(options, true)) {
                    if (!options.data) {
                        this._initXHRData(options);
                    }
                    this._initProgressListener(options);
                }
                if (options.postMessage) {
                    // Setting the dataType to postmessage enables the
                    // postMessage transport:
                    options.dataType = 'postmessage ' + (options.dataType || '');
                }
            } else {
                this._initIframeSettings(options, 'iframe');
            }
        },

        _getParamName: function (options) {
            var fileInput = $(options.fileInput),
                paramName = options.paramName;
            if (!paramName) {
                paramName = [];
                fileInput.each(function () {
                    var input = $(this),
                        name = input.prop('name') || 'files[]',
                        i = (input.prop('files') || [1]).length;
                    while (i) {
                        paramName.push(name);
                        i -= 1;
                    }
                });
                if (!paramName.length) {
                    paramName = [fileInput.prop('name') || 'files[]'];
                }
            } else if (!$.isArray(paramName)) {
                paramName = [paramName];
            }
            return paramName;
        },

        _initFormSettings: function (options) {
            // Retrieve missing options from the input field and the
            // associated form, if available:
            if (!options.form || !options.form.length) {
                options.form = $(options.fileInput.prop('form'));
            }
            options.paramName = this._getParamName(options);
            if (!options.url) {
                options.url = options.form.prop('action') || location.href;
            }
            // The HTTP request method must be "POST" or "PUT":
            options.type = (options.type || options.form.prop('method') || '')
                .toUpperCase();
            if (options.type !== 'POST' && options.type !== 'PUT') {
                options.type = 'POST';
            }
        },

        _getAJAXSettings: function (data) {
            var options = $.extend({}, this.options, data);
            this._initFormSettings(options);
            this._initDataSettings(options);
            return options;
        },

        // Maps jqXHR callbacks to the equivalent
        // methods of the given Promise object:
        _enhancePromise: function (promise) {
            promise.success = promise.done;
            promise.error = promise.fail;
            promise.complete = promise.always;
            return promise;
        },

        // Creates and returns a Promise object enhanced with
        // the jqXHR methods abort, success, error and complete:
        _getXHRPromise: function (resolveOrReject, context, args) {
            var dfd = $.Deferred(),
                promise = dfd.promise();
            context = context || this.options.context || promise;
            if (resolveOrReject === true) {
                dfd.resolveWith(context, args);
            } else if (resolveOrReject === false) {
                dfd.rejectWith(context, args);
            }
            promise.abort = dfd.promise;
            return this._enhancePromise(promise);
        },

        // Uploads a file in multiple, sequential requests
        // by splitting the file up in multiple blob chunks.
        // If the second parameter is true, only tests if the file
        // should be uploaded in chunks, but does not invoke any
        // upload requests:
        _chunkedUpload: function (options, testOnly) {
            var that = this,
                file = options.files[0],
                fs = file.size,
                ub = options.uploadedBytes = options.uploadedBytes || 0,
                mcs = options.maxChunkSize || fs,
            // Use the Blob methods with the slice implementation
            // according to the W3C Blob API specification:
                slice = file.webkitSlice || file.mozSlice || file.slice,
                upload,
                n,
                jqXHR,
                pipe;
            if (!(this._isXHRUpload(options) && slice && (ub || mcs < fs)) ||
                options.data) {
                return false;
            }
            if (testOnly) {
                return true;
            }
            if (ub >= fs) {
                file.error = 'uploadedBytes';
                return this._getXHRPromise(
                    false,
                    options.context,
                    [null, 'error', file.error]
                );
            }
            // n is the number of blobs to upload,
            // calculated via filesize, uploaded bytes and max chunk size:
            n = Math.ceil((fs - ub) / mcs);
            // The chunk upload method accepting the chunk number as parameter:
            upload = function (i) {
                if (!i) {
                    return that._getXHRPromise(true, options.context);
                }
                // Upload the blobs in sequential order:
                return upload(i -= 1).pipe(function () {
                    // Clone the options object for each chunk upload:
                    var o = $.extend({}, options);
                    o.blob = slice.call(
                        file,
                        ub + i * mcs,
                        ub + (i + 1) * mcs
                    );
                    // Store the current chunk size, as the blob itself
                    // will be dereferenced after data processing:
                    o.chunkSize = o.blob.size;
                    // Process the upload data (the blob and potential form data):
                    that._initXHRData(o);
                    // Add progress listeners for this chunk upload:
                    that._initProgressListener(o);
                    jqXHR = ($.ajax(o) || that._getXHRPromise(false, o.context))
                        .done(function () {
                            // Create a progress event if upload is done and
                            // no progress event has been invoked for this chunk:
                            if (!o.loaded) {
                                that._onProgress($.Event('progress', {
                                    lengthComputable: true,
                                    loaded: o.chunkSize,
                                    total: o.chunkSize
                                }), o);
                            }
                            options.uploadedBytes = o.uploadedBytes +=
                                o.chunkSize;
                        });
                    return jqXHR;
                });
            };
            // Return the piped Promise object, enhanced with an abort method,
            // which is delegated to the jqXHR object of the current upload,
            // and jqXHR callbacks mapped to the equivalent Promise methods:
            pipe = upload(n);
            pipe.abort = function () {
                return jqXHR.abort();
            };
            return this._enhancePromise(pipe);
        },

        _beforeSend: function (e, data) {
            if (this._active === 0) {
                // the start callback is triggered when an upload starts
                // and no other uploads are currently running,
                // equivalent to the global ajaxStart event:
                this._trigger('start');
            }
            this._active += 1;
            // Initialize the global progress values:
            this._loaded += data.uploadedBytes || 0;
            this._total += this._getTotal(data.files);
        },

        _onDone: function (result, textStatus, jqXHR, options) {
            if (!this._isXHRUpload(options)) {
                // Create a progress event for each iframe load:
                this._onProgress($.Event('progress', {
                    lengthComputable: true,
                    loaded: 1,
                    total: 1
                }), options);
            }
            options.result = result;
            options.textStatus = textStatus;
            options.jqXHR = jqXHR;
            this._trigger('done', null, options);
        },

        _onFail: function (jqXHR, textStatus, errorThrown, options) {
            options.jqXHR = jqXHR;
            options.textStatus = textStatus;
            options.errorThrown = errorThrown;
            this._trigger('fail', null, options);
            if (options.recalculateProgress) {
                // Remove the failed (error or abort) file upload from
                // the global progress calculation:
                this._loaded -= options.loaded || options.uploadedBytes || 0;
                this._total -= options.total || this._getTotal(options.files);
            }
        },

        _onAlways: function (jqXHRorResult, textStatus, jqXHRorError, options) {
            this._active -= 1;
            options.textStatus = textStatus;
            if (jqXHRorError && jqXHRorError.always) {
                options.jqXHR = jqXHRorError;
                options.result = jqXHRorResult;
            } else {
                options.jqXHR = jqXHRorResult;
                options.errorThrown = jqXHRorError;
            }
            this._trigger('always', null, options);
            if (this._active === 0) {
                // The stop callback is triggered when all uploads have
                // been completed, equivalent to the global ajaxStop event:
                this._trigger('stop');
                // Reset the global progress values:
                this._loaded = this._total = 0;
            }
        },

        _onSend: function (e, data) {
            var that = this,
                jqXHR,
                slot,
                pipe,
                options = that._getAJAXSettings(data),
                send = function (resolve, args) {
                    that._sending += 1;
                    jqXHR = jqXHR || (
                        (resolve !== false &&
                            that._trigger('send', e, options) !== false &&
                            (that._chunkedUpload(options) || $.ajax(options))) ||
                            that._getXHRPromise(false, options.context, args)
                        ).done(function (result, textStatus, jqXHR) {
                            that._onDone(result, textStatus, jqXHR, options);
                        }).fail(function (jqXHR, textStatus, errorThrown) {
                            that._onFail(jqXHR, textStatus, errorThrown, options);
                        }).always(function (jqXHRorResult, textStatus, jqXHRorError) {
                            that._sending -= 1;
                            that._onAlways(
                                jqXHRorResult,
                                textStatus,
                                jqXHRorError,
                                options
                            );
                            if (options.limitConcurrentUploads &&
                                options.limitConcurrentUploads > that._sending) {
                                // Start the next queued upload,
                                // that has not been aborted:
                                var nextSlot = that._slots.shift();
                                while (nextSlot) {
                                    if (!nextSlot.isRejected()) {
                                        nextSlot.resolve();
                                        break;
                                    }
                                    nextSlot = that._slots.shift();
                                }
                            }
                        });
                    return jqXHR;
                };
            this._beforeSend(e, options);
            if (this.options.sequentialUploads ||
                (this.options.limitConcurrentUploads &&
                    this.options.limitConcurrentUploads <= this._sending)) {
                if (this.options.limitConcurrentUploads > 1) {
                    slot = $.Deferred();
                    this._slots.push(slot);
                    pipe = slot.pipe(send);
                } else {
                    pipe = (this._sequence = this._sequence.pipe(send, send));
                }
                // Return the piped Promise object, enhanced with an abort method,
                // which is delegated to the jqXHR object of the current upload,
                // and jqXHR callbacks mapped to the equivalent Promise methods:
                pipe.abort = function () {
                    var args = [undefined, 'abort', 'abort'];
                    if (!jqXHR) {
                        if (slot) {
                            slot.rejectWith(args);
                        }
                        return send(false, args);
                    }
                    return jqXHR.abort();
                };
                return this._enhancePromise(pipe);
            }
            return send();
        },

        _onAdd: function (e, data) {
            var that = this,
                result = true,
                options = $.extend({}, this.options, data),
                limit = options.limitMultiFileUploads,
                paramName = this._getParamName(options),
                paramNameSet,
                paramNameSlice,
                fileSet,
                i;
            if (!(options.singleFileUploads || limit) ||
                !this._isXHRUpload(options)) {
                fileSet = [data.files];
                paramNameSet = [paramName];
            } else if (!options.singleFileUploads && limit) {
                fileSet = [];
                paramNameSet = [];
                for (i = 0; i < data.files.length; i += limit) {
                    fileSet.push(data.files.slice(i, i + limit));
                    paramNameSlice = paramName.slice(i, i + limit);
                    if (!paramNameSlice.length) {
                        paramNameSlice = paramName;
                    }
                    paramNameSet.push(paramNameSlice);
                }
            } else {
                paramNameSet = paramName;
            }
            data.originalFiles = data.files;
            $.each(fileSet || data.files, function (index, element) {
                var newData = $.extend({}, data);
                newData.files = fileSet ? element : [element];
                newData.paramName = paramNameSet[index];
                newData.submit = function () {
                    newData.jqXHR = this.jqXHR =
                        (that._trigger('submit', e, this) !== false) &&
                            that._onSend(e, this);
                    return this.jqXHR;
                };
                return (result = that._trigger('add', e, newData));
            });
            return result;
        },

        // File Normalization for Gecko 1.9.1 (Firefox 3.5) support:
        _normalizeFile: function (index, file) {
            if (file.name === undefined && file.size === undefined) {
                file.name = file.fileName;
                file.size = file.fileSize;
            }
        },

        _replaceFileInput: function (input) {
            var inputClone = input.clone(true);
            $('<form></form>').append(inputClone)[0].reset();
            // Detaching allows to insert the fileInput on another form
            // without loosing the file input value:
            input.after(inputClone).detach();
            // Avoid memory leaks with the detached file input:
            $.cleanData(input.unbind('remove'));
            // Replace the original file input element in the fileInput
            // collection with the clone, which has been copied including
            // event handlers:
            this.options.fileInput = this.options.fileInput.map(function (i, el) {
                if (el === input[0]) {
                    return inputClone[0];
                }
                return el;
            });
            // If the widget has been initialized on the file input itself,
            // override this.element with the file input clone:
            if (input[0] === this.element[0]) {
                this.element = inputClone;
            }
        },

        _onChange: function (e) {
            var that = e.data.fileupload,
                data = {
                    files: $.each($.makeArray(e.target.files), that._normalizeFile),
                    fileInput: $(e.target),
                    form: $(e.target.form)
                };
            if (!data.files.length) {
                // If the files property is not available, the browser does not
                // support the File API and we add a pseudo File object with
                // the input value as name with path information removed:
                data.files = [{name: e.target.value.replace(/^.*\\/, '')}];
            }
            if (that.options.replaceFileInput) {
                that._replaceFileInput(data.fileInput);
            }
            if (that._trigger('change', e, data) === false ||
                that._onAdd(e, data) === false) {
                return false;
            }
        },

        _onPaste: function (e) {
            var that = e.data.fileupload,
                cbd = e.originalEvent.clipboardData,
                items = (cbd && cbd.items) || [],
                data = {files: []};
            $.each(items, function (index, item) {
                var file = item.getAsFile && item.getAsFile();
                if (file) {
                    data.files.push(file);
                }
            });
            if (that._trigger('paste', e, data) === false ||
                that._onAdd(e, data) === false) {
                return false;
            }
        },

        _onDrop: function (e) {
            var that = e.data.fileupload,
                dataTransfer = e.dataTransfer = e.originalEvent.dataTransfer,
                data = {
                    files: $.each(
                        $.makeArray(dataTransfer && dataTransfer.files),
                        that._normalizeFile
                    )
                };
            if (that._trigger('drop', e, data) === false ||
                that._onAdd(e, data) === false) {
                return false;
            }
            e.preventDefault();
        },

        _onDragOver: function (e) {
            var that = e.data.fileupload,
                dataTransfer = e.dataTransfer = e.originalEvent.dataTransfer;
            if (that._trigger('dragover', e) === false) {
                return false;
            }
            if (dataTransfer) {
                dataTransfer.dropEffect = dataTransfer.effectAllowed = 'copy';
            }
            e.preventDefault();
        },

        _initEventHandlers: function () {
            var ns = this.options.namespace;
            if (this._isXHRUpload(this.options)) {
                this.options.dropZone
                    .bind('dragover.' + ns, {fileupload: this}, this._onDragOver)
                    .bind('drop.' + ns, {fileupload: this}, this._onDrop)
                    .bind('paste.' + ns, {fileupload: this}, this._onPaste);
            }
            this.options.fileInput
                .bind('change.' + ns, {fileupload: this}, this._onChange);
        },

        _destroyEventHandlers: function () {
            var ns = this.options.namespace;
            this.options.dropZone
                .unbind('dragover.' + ns, this._onDragOver)
                .unbind('drop.' + ns, this._onDrop)
                .unbind('paste.' + ns, this._onPaste);
            this.options.fileInput
                .unbind('change.' + ns, this._onChange);
        },

        _setOption: function (key, value) {
            var refresh = $.inArray(key, this._refreshOptionsList) !== -1;
            if (refresh) {
                this._destroyEventHandlers();
            }
            $.Widget.prototype._setOption.call(this, key, value);
            if (refresh) {
                this._initSpecialOptions();
                this._initEventHandlers();
            }
        },

        _initSpecialOptions: function () {
            var options = this.options;
            if (options.fileInput === undefined) {
                options.fileInput = this.element.is('input:file') ?
                    this.element : this.element.find('input:file');
            } else if (!(options.fileInput instanceof $)) {
                options.fileInput = $(options.fileInput);
            }
            if (!(options.dropZone instanceof $)) {
                options.dropZone = $(options.dropZone);
            }
        },

        _create: function () {
            var options = this.options,
                dataOpts = $.extend({}, this.element.data());
            dataOpts[this.widgetName] = undefined;
            $.extend(options, dataOpts);
            options.namespace = options.namespace || this.widgetName;
            this._initSpecialOptions();
            this._slots = [];
            this._sequence = this._getXHRPromise(true);
            this._sending = this._active = this._loaded = this._total = 0;
            this._initEventHandlers();
        },

        destroy: function () {
            this._destroyEventHandlers();
            $.Widget.prototype.destroy.call(this);
        },

        enable: function () {
            $.Widget.prototype.enable.call(this);
            this._initEventHandlers();
        },

        disable: function () {
            this._destroyEventHandlers();
            $.Widget.prototype.disable.call(this);
        },

        // This method is exposed to the widget API and allows adding files
        // using the fileupload API. The data parameter accepts an object which
        // must have a files property and can contain additional options:
        // .fileupload('add', {files: filesList});
        add: function (data) {
            if (!data || this.options.disabled) {
                return;
            }
            data.files = $.each($.makeArray(data.files), this._normalizeFile);
            this._onAdd(null, data);
        },

        // This method is exposed to the widget API and allows sending files
        // using the fileupload API. The data parameter accepts an object which
        // must have a files property and can contain additional options:
        // .fileupload('send', {files: filesList});
        // The method returns a Promise object for the file upload call.
        send: function (data) {
            if (data && !this.options.disabled) {
                data.files = $.each($.makeArray(data.files), this._normalizeFile);
                if (data.files.length) {
                    return this._onSend(null, data);
                }
            }
            return this._getXHRPromise(false, data && data.context);
        }

    });

}));

/*global jQuery:true, ko:true */
// Fileupload
(function ($, ko) {
    "use strict";
    ko.bindingHandlers.fileupload = {
        init:function (element, valueAccessor) {
            var options = ko.utils.unwrapObservable(valueAccessor());
            // TODO pass options to the customFileInput
            $(element).fileupload(options);

            ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                $(element).fileupload('destroy');
            });
        },
        update:function (element, valueAccessor, allBindingsAccessor, context) {
            //handle programmatic updates to the observable
            var options = ko.utils.unwrapObservable(valueAccessor());
            $(element).fileupload('option', options);

        }
    };
})(jQuery, ko);
// - end of Fileupload
/*global jQuery:true, ko:true */
// Mask/money input
// Provides the .mask and .money binding handlers

/*
 Masked Input plugin for jQuery
 Copyright (c) 2007-@Year Josh Bush (digitalbush.com)
 Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
 Version: @version
 */
(function ($) {
    var pasteEventName = ($.browser.msie ? 'paste' : 'input') + ".mask";
    var iPhone = (window.orientation != undefined);

    $.mask = {
        //Predefined character definitions
        definitions:{
            '9':"[0-9]",
            'a':"[A-Za-z]",
            '*':"[A-Za-z0-9]"
        },
        dataName:"rawMaskFn"
    };

    $.fn.extend({
        //Helper Function for Caret positioning
        caret:function (begin, end) {
            if (this.length == 0) return;
            if (typeof begin == 'number') {
                end = (typeof end == 'number') ? end : begin;
                return this.each(function () {
                    if (this.setSelectionRange) {
                        this.setSelectionRange(begin, end);
                    } else if (this.createTextRange) {
                        var range = this.createTextRange();
                        range.collapse(true);
                        range.moveEnd('character', end);
                        range.moveStart('character', begin);
                        range.select();
                    }
                });
            } else {
                if (this[0].setSelectionRange) {
                    begin = this[0].selectionStart;
                    end = this[0].selectionEnd;
                } else if (document.selection && document.selection.createRange) {
                    var range = document.selection.createRange();
                    begin = 0 - range.duplicate().moveStart('character', -100000);
                    end = begin + range.text.length;
                }
                return { begin:begin, end:end };
            }
        },
        unmask:function () {
            return this.trigger("unmask");
        },
        mask:function (mask, settings) {
            if (!mask && this.length > 0) {
                var input = $(this[0]);
                return input.data($.mask.dataName)();
            }
            settings = $.extend({
                placeholder:"_",
                completed:null
            }, settings);

            var defs = $.mask.definitions;
            var tests = [];
            var partialPosition = mask.length;
            var firstNonMaskPos = null;
            var len = mask.length;

            $.each(mask.split(""), function (i, c) {
                if (c == '?') {
                    len--;
                    partialPosition = i;
                } else if (defs[c]) {
                    tests.push(new RegExp(defs[c]));
                    if (firstNonMaskPos == null)
                        firstNonMaskPos = tests.length - 1;
                } else {
                    tests.push(null);
                }
            });

            return this.trigger("unmask").each(function () {
                var input = $(this);
                var buffer = $.map(mask.split(""), function (c, i) {
                    if (c != '?') return defs[c] ? settings.placeholder : c
                });
                var focusText = input.val();

                function seekNext(pos) {
                    while (++pos <= len && !tests[pos]);
                    return pos;
                }

                function seekPrev(pos) {
                    while (--pos >= 0 && !tests[pos]);
                    return pos;
                }


                function shiftL(begin, end) {
                    if (begin < 0)
                        return;
                    for (var i = begin, j = seekNext(end); i < len; i++) {
                        if (tests[i]) {
                            if (j < len && tests[i].test(buffer[j])) {
                                buffer[i] = buffer[j];
                                buffer[j] = settings.placeholder;
                            } else
                                break;
                            j = seekNext(j);
                        }
                    }
                    writeBuffer();
                    input.caret(Math.max(firstNonMaskPos, begin));
                }


                function shiftR(pos) {
                    for (var i = pos, c = settings.placeholder; i < len; i++) {
                        if (tests[i]) {
                            var j = seekNext(i);
                            var t = buffer[i];
                            buffer[i] = c;
                            if (j < len && tests[j].test(t))
                                c = t;
                            else
                                break;
                        }
                    }
                }


                function keydownEvent(e) {
                    var k = e.which;

                    //backspace, delete, and escape get special treatment
                    if (k == 8 || k == 46 || (iPhone && k == 127)) {
                        var pos = input.caret(),
                            begin = pos.begin,
                            end = pos.end;

                        if (end - begin == 0) {
                            begin = k != 46 ? seekPrev(begin) : (end = seekNext(begin - 1));
                            end = k == 46 ? seekNext(end) : end;
                        }
                        clearBuffer(begin, end);
                        shiftL(begin, end - 1);

                        return false;
                    } else if (k == 27) {//escape
                        input.val(focusText);
                        input.caret(0, checkVal());
                        return false;
                    }
                }


                function keypressEvent(e) {
                    var k = e.which,
                        pos = input.caret();
                    if (e.ctrlKey || e.altKey || e.metaKey || k < 32) {//Ignore
                        return true;
                    } else if (k) {
                        if (pos.end - pos.begin != 0) {
                            clearBuffer(pos.begin, pos.end);
                            shiftL(pos.begin, pos.end - 1);
                        }

                        var p = seekNext(pos.begin - 1);
                        if (p < len) {
                            var c = String.fromCharCode(k);
                            if (tests[p].test(c)) {
                                shiftR(p);
                                buffer[p] = c;
                                writeBuffer();
                                var next = seekNext(p);
                                input.caret(next);
                                if (settings.completed && next >= len)
                                    settings.completed.call(input);
                            }
                        }
                        return false;
                    }
                }

                function clearBuffer(start, end) {
                    for (var i = start; i < end && i < len; i++) {
                        if (tests[i])
                            buffer[i] = settings.placeholder;
                    }
                }

                function writeBuffer() {
                    return input.val(buffer.join('')).val();
                }

                function checkVal(allow) {
                    //try to place characters where they belong
                    var test = input.val();
                    var lastMatch = -1;
                    for (var i = 0, pos = 0; i < len; i++) {
                        if (tests[i]) {
                            buffer[i] = settings.placeholder;
                            while (pos++ < test.length) {
                                var c = test.charAt(pos - 1);
                                if (tests[i].test(c)) {
                                    buffer[i] = c;
                                    lastMatch = i;
                                    break;
                                }
                            }
                            if (pos > test.length)
                                break;
                        } else if (buffer[i] == test.charAt(pos) && i != partialPosition) {
                            pos++;
                            lastMatch = i;
                        }
                    }
                    if (!allow && lastMatch + 1 < partialPosition) {
                        input.val("");
                        clearBuffer(0, len);
                    } else if (allow || lastMatch + 1 >= partialPosition) {
                        writeBuffer();
                        if (!allow) input.val(input.val().substring(0, lastMatch + 1));
                    }
                    return (partialPosition ? i : firstNonMaskPos);
                }

                input.data($.mask.dataName, function () {
                    return $.map(buffer,
                        function (c, i) {
                            return tests[i] && c != settings.placeholder ? c : null;
                        }).join('');
                });

                if (!input.attr("readonly"))
                    input
                        .one("unmask", function () {
                            input
                                .unbind(".mask")
                                .removeData($.mask.dataName);
                        })
                        .bind("focus.mask", function () {
                            focusText = input.val();
                            var pos = checkVal();
                            writeBuffer();
                            var moveCaret = function () {
                                if (pos == mask.length)
                                    input.caret(0, pos);
                                else
                                    input.caret(pos);
                            };
                            ($.browser.msie ? moveCaret : function () {
                                setTimeout(moveCaret, 0)
                            })();
                        })
                        .bind("blur.mask", function () {
                            checkVal();
                            if (input.val() != focusText)
                                input.change();
                        })
                        .bind("keydown.mask", keydownEvent)
                        .bind("keypress.mask", keypressEvent)
                        .bind(pasteEventName, function () {
                            setTimeout(function () {
                                input.caret(checkVal(true));
                            }, 0);
                        });

                checkVal(); //Perform initial check for existing values
            });
        }
    });
})(jQuery);

/*
 * @Copyright (c) 2011 Aurélio Saraiva, Diego Plentz
 * @Page http://github.com/plentz/jquery-maskmoney
 * try at http://plentz.org/maskmoney

 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

/*
 * @Version: 1.4.1
 * @Release: 2011-11-01
 */
(function ($) {
    $.fn.maskMoney = function (settings) {
        settings = $.extend({
            symbol:'US$',
            showSymbol:false,
            symbolStay:false,
            thousands:',',
            decimal:'.',
            precision:2,
            defaultZero:true,
            allowZero:false,
            allowNegative:false
        }, settings);

        return this.each(function () {
            var input = $(this);
            var dirty = false;

            function markAsDirty() {
                dirty = true;
            }

            function clearDirt() {
                dirty = false;
            }

            function keypressEvent(e) {
                e = e || window.event;
                var k = e.charCode || e.keyCode || e.which;
                if (k == undefined) return false; //needed to handle an IE "special" event
                if (input.attr('readonly') && (k != 13 && k != 9)) return false; // don't allow editing of readonly fields but allow tab/enter

                if (k < 48 || k > 57) { // any key except the numbers 0-9
                    if (k == 45) { // -(minus) key
                        markAsDirty();
                        input.val(changeSign(input));
                        return false;
                    } else if (k == 43) { // +(plus) key
                        markAsDirty();
                        input.val(input.val().replace('-', ''));
                        return false;
                    } else if (k == 13 || k == 9) { // enter key or tab key
                        if (dirty) {
                            clearDirt();
                            $(this).change();
                        }
                        return true;
                    } else if (k == 37 || k == 39) { // left arrow key or right arrow key
                        return true;
                    } else { // any other key with keycode less than 48 and greater than 57
                        preventDefault(e);
                        return true;
                    }
                } else if (input.val().length >= input.attr('maxlength')) {
                    return false;
                } else {
                    preventDefault(e);

                    var key = String.fromCharCode(k);
                    var x = input.get(0);
                    var selection = input.getInputSelection(x);
                    var startPos = selection.start;
                    var endPos = selection.end;
                    x.value = x.value.substring(0, startPos) + key + x.value.substring(endPos, x.value.length);
                    maskAndPosition(x, startPos + 1);
                    markAsDirty();
                    return false;
                }
            }

            function keydownEvent(e) {
                e = e || window.event;
                var k = e.charCode || e.keyCode || e.which;
                if (k == undefined) return false; //needed to handle an IE "special" event
                if (input.attr('readonly') && (k != 13 && k != 9)) return false; // don't allow editing of readonly fields but allow tab/enter

                var x = input.get(0);
                var selection = input.getInputSelection(x);
                var startPos = selection.start;
                var endPos = selection.end;

                if (k == 8) { // backspace key
                    preventDefault(e);

                    if (startPos == endPos) {
                        // Remove single character
                        x.value = x.value.substring(0, startPos - 1) + x.value.substring(endPos, x.value.length);
                        startPos = startPos - 1;
                    } else {
                        // Remove multiple characters
                        x.value = x.value.substring(0, startPos) + x.value.substring(endPos, x.value.length);
                    }
                    maskAndPosition(x, startPos);
                    markAsDirty();
                    return false;
                } else if (k == 9) { // tab key
                    if (dirty) {
                        $(this).change();
                        clearDirt();
                    }
                    return true;
                } else if (k == 46 || k == 63272) { // delete key (with special case for safari)
                    preventDefault(e);
                    if (x.selectionStart == x.selectionEnd) {
                        // Remove single character
                        x.value = x.value.substring(0, startPos) + x.value.substring(endPos + 1, x.value.length);
                    } else {
                        //Remove multiple characters
                        x.value = x.value.substring(0, startPos) + x.value.substring(endPos, x.value.length);
                    }
                    maskAndPosition(x, startPos);
                    markAsDirty();
                    return false;
                } else { // any other key
                    return true;
                }
            }

            function focusEvent(e) {
                var mask = getDefaultMask();
                if (input.val() == mask) {
                    input.val('');
                } else if (input.val() == '' && settings.defaultZero) {
                    input.val(setSymbol(mask));
                } else {
                    input.val(setSymbol(input.val()));
                }
                if (this.createTextRange) {
                    var textRange = this.createTextRange();
                    textRange.collapse(false); // set the cursor at the end of the input
                    textRange.select();
                }
            }

            function blurEvent(e) {
                if ($.browser.msie) {
                    keypressEvent(e);
                }

                if (input.val() == '' || input.val() == setSymbol(getDefaultMask()) || input.val() == settings.symbol) {
                    if (!settings.allowZero) input.val('');
                    else if (!settings.symbolStay) input.val(getDefaultMask());
                    else input.val(setSymbol(getDefaultMask()));
                } else {
                    if (!settings.symbolStay) input.val(input.val().replace(settings.symbol, ''));
                    else if (settings.symbolStay && input.val() == settings.symbol) input.val(setSymbol(getDefaultMask()));
                }
            }

            function preventDefault(e) {
                if (e.preventDefault) { //standard browsers
                    e.preventDefault();
                } else { // internet explorer
                    e.returnValue = false
                }
            }

            function maskAndPosition(x, startPos) {
                var originalLen = input.val().length;
                input.val(maskValue(x.value));
                var newLen = input.val().length;
                startPos = startPos - (originalLen - newLen);
                input.setCursorPosition(startPos);
            }

            function maskValue(v) {
                v = v.replace(settings.symbol, '');

                var strCheck = '0123456789';
                var len = v.length;
                var a = '', t = '', neg = '';

                if (len != 0 && v.charAt(0) == '-') {
                    v = v.replace('-', '');
                    if (settings.allowNegative) {
                        neg = '-';
                    }
                }

                if (len == 0) {
                    if (!settings.defaultZero) return t;
                    t = '0.00';
                }

                for (var i = 0; i < len; i++) {
                    if ((v.charAt(i) != '0') && (v.charAt(i) != settings.decimal)) break;
                }

                for (; i < len; i++) {
                    if (strCheck.indexOf(v.charAt(i)) != -1) a += v.charAt(i);
                }

                var n = parseFloat(a);
                n = isNaN(n) ? 0 : n / Math.pow(10, settings.precision);

                input.trigger('money.change', [ n ]);

                return setValue(n);
            }

            function setValue(n) {
                if (typeof(n) != 'number') {
                    n = 0;
                }

                var t = n.toFixed(settings.precision),
                    neg = n < 0 ? '-' : '';

                var i = settings.precision == 0 ? 0 : 1;
                var p, d = (t = t.split('.'))[i].substr(0, settings.precision);
                for (p = (t = t[0]).length; (p -= 3) >= 1;) {
                    t = t.substr(0, p) + settings.thousands + t.substr(p);
                }

                return (settings.precision > 0)
                    ? setSymbol(neg + t + settings.decimal + d + Array((settings.precision + 1) - d.length).join(0))
                    : setSymbol(neg + t);
            }

            function mask() {
                var value = input.val();
                input.val(maskValue(value));
            }

            function getDefaultMask() {
                var n = parseFloat('0') / Math.pow(10, settings.precision);
                return (n.toFixed(settings.precision)).replace(new RegExp('\\.', 'g'), settings.decimal);
            }

            function setSymbol(v) {
                if (settings.showSymbol) {
                    if (v.substr(0, settings.symbol.length) != settings.symbol) return settings.symbol + v;
                }
                return v;
            }

            function changeSign(i) {
                if (settings.allowNegative) {
                    var vic = i.val();
                    if (i.val() != '' && i.val().charAt(0) == '-') {
                        return i.val().replace('-', '');
                    } else {
                        return '-' + i.val();
                    }
                } else {
                    return i.val();
                }
            }

            input.bind('keypress.maskMoney', keypressEvent);
            input.bind('keydown.maskMoney', keydownEvent);
            input.bind('blur.maskMoney', blurEvent);
            input.bind('focus.maskMoney', focusEvent);
            input.bind('mask', mask);
            input.bind('money.update', function (event, newValue) {
                input.val(setValue(newValue));
            });

            input.one('unmaskMoney', function () {
                input.unbind('.maskMoney');

                if ($.browser.msie) {
                    this.onpaste = null;
                } else if ($.browser.mozilla) {
                    this.removeEventListener('input', blurEvent, false);
                }
            });
        });
    };

    $.fn.unmaskMoney = function () {
        return this.trigger('unmaskMoney');
    };

//    Changed due to incompabilities with the mask() plugin
    $.fn.maskValue = function () {
        return this.trigger('mask');
    };

    $.fn.setCursorPosition = function (pos) {
        this.each(function (index, elem) {
            if (elem.setSelectionRange) {
                elem.focus();
                elem.setSelectionRange(pos, pos);
            } else if (elem.createTextRange) {
                var range = elem.createTextRange();
                range.collapse(true);
                range.moveEnd('character', pos);
                range.moveStart('character', pos);
                range.select();
            }
        });
        return this;
    };

    $.fn.getInputSelection = function (el) {
        var start = 0, end = 0, normalizedValue, range, textInputRange, len, endRange;

        if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
            start = el.selectionStart;
            end = el.selectionEnd;
        } else {
            range = document.selection.createRange();

            if (range && range.parentElement() == el) {
                len = el.value.length;
                normalizedValue = el.value.replace(/\r\n/g, "\n");

                // Create a working TextRange that lives only in the input
                textInputRange = el.createTextRange();
                textInputRange.moveToBookmark(range.getBookmark());

                // Check if the start and end of the selection are at the very end
                // of the input, since moveStart/moveEnd doesn't return what we want
                // in those cases
                endRange = el.createTextRange();
                endRange.collapse(false);

                if (textInputRange.compareEndPoints("StartToEnd", endRange) > -1) {
                    start = end = len;
                } else {
                    start = -textInputRange.moveStart("character", -len);
                    start += normalizedValue.slice(0, start).split("\n").length - 1;

                    if (textInputRange.compareEndPoints("EndToEnd", endRange) > -1) {
                        end = len;
                    } else {
                        end = -textInputRange.moveEnd("character", -len);
                        end += normalizedValue.slice(0, end).split("\n").length - 1;
                    }
                }
            }
        }

        return {
            start:start,
            end:end
        };
    }
})(jQuery);

(function ($, ko) {
    ko.bindingHandlers.mask = {
        init:function (element, valueAccessor) {
            var $element = $(element),
                params = valueAccessor();
            $element.mask(params);
        }
    };


    ko.bindingHandlers.money = {
        init:function (element, valueAccessor, allBindingsAccessor) {
            var $element = $(element);

            //handle disposal (if KO removes by the template binding)
            ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                $element.datepicker("destroy");
            });

            //initialize datepicker with some optional options
            var options = allBindingsAccessor().maskSettings || {};
            $element.maskMoney(options);

            //handle the field changing
            // on money.change value is ALWAYS a float
            // TODO verificar se value é Date ou String e configurar de acordo
            ko.utils.registerEventHandler(element, "money.change", function (event, value) {
                var observable = valueAccessor();

                observable(value);
            });


        },
        update:function (element, valueAccessor) {
            var value = ko.utils.unwrapObservable(valueAccessor());

            if (typeof(value) != 'number') {
                value = parseFloat(value) || 0;
            }

            $(element).trigger("money.update", [ value ]);
        }
    };
})(jQuery, ko);
// - end of mask/money input/*global $: true, ko: true, tinymce: true */
// Rich Text Editor
// Depends on tinymce, options are passed via the tinymceOptions binding
// Binding structure taken from http://jsfiddle.net/rniemeyer/BwQ4k/
(undefined !== window.tinymce) && (function ($, ko, tinymce) {
    "use strict";

    ko.bindingHandlers.tinymce = {
        init:function (element, valueAccessor, allBindingsAccessor, context) {
            var options = allBindingsAccessor().tinymceOptions || {};
            var modelValue = valueAccessor();

            $(element).val(ko.utils.unwrapObservable(modelValue));

            if (ko.isWriteableObservable(modelValue)) {
                options.setup = function (ed) {
                    ed.onChange.add(function (ed, l) {
                        modelValue(ed.getContent());
                    });
                };
            }

            if (!element.id) {
                element.id = 'mp_tinymce_' + new Date().getTime();
            }

            options = $.extend({
                theme:"simple"
            }, options);

            options.mode = 'exact';
            options.elements = element.id;

            tinymce.init(options);

            //tinyMCE.execCommand('mceAddControl', false, element);
            ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                tinymce.execCommand('mceFocus', false, element.id);
                tinymce.execCommand('mceRemoveControl', false, element.id);
            });
        },
        update:function (element, valueAccessor, allBindingsAccessor, context) {
            //handle programmatic updates to the observable
            var value = ko.utils.unwrapObservable(valueAccessor());
            var editor = tinymce.get(element.id);
            // TODO can't remember exactly why this is commented, had issues with multiple editors and/or focus problems after update
//        if(editor) {
//            if(editor.getContent() != value) {
//                editor.setContent(value)
//            }
//        }
        }
    };
})(jQuery, ko, tinymce);
// - end of tinymce

/*global jQuery: true, metaproject: true, ko: true */

// metaproject ui components
(function(window, $, metaproject, ko) {
    "use strict";

    metaproject.ui = metaproject.ui || {};

    metaproject.ui.Grid = function(data, params) {
        var self = this;

        params = $.extend({}, { columns: [], actions: []}, params);
        // data is an array
        self.data = data;
        self.columns = params.columns;
        self.actions = params.actions;


    };


    /* UI Alerts */
    $.fn.alert = function (kind, message) {
        var options = {
            level: kind,
            block: false,
            delay: 250
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

    /* Custom Binding Handlers */

    ko.bindingHandlers.autocomplete = {
        init: function (element, valueAccessor, allBindingsAccessor, viewModel) {

            var $element = jQuery(element),
                params = valueAccessor();

            //handle disposal
            ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                $element.autocomplete("destroy");
            });

            // treat String, callback or Array as source
            if (typeof(params) === 'string' || typeof(params) === 'function' || params instanceof Array) {
                params = { source: params };
            }

            var $autocomplete = $element.autocomplete(params).data('autocomplete');

            // Custom render callback http://jqueryui.com/demos/autocomplete/#custom-data
            // TODO render as string => ko templates ?
            if (undefined !== params.renderItem) {
                $autocomplete._renderItem = params.renderItem;
            }

            if (undefined !== params.renderMenu) {
                $autocomplete._renderMenu = params.renderMenu;
            }
        }
    };

    ko.bindingHandlers.dialog = {
        init: function (element, valueAccessor, allBindingsAccessor, viewModel) {
            var $element = jQuery(element),
                params = valueAccessor();

            //handle disposal (if KO removes by the template binding)
            ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                $element.dialog("destroy");
            });

            jQuery.extend(params, { autoOpen: false });

            $element.dialog(params);
        }
    };


    ko.bindingHandlers.icon = {
        init: function (element, valueAccessor) {

            var icon = '<span class="ui-icon ui-icon-' + valueAccessor() + '"></span>';

            jQuery(element).prepend(icon);
        }
    };
})(window, jQuery, metaproject, ko);