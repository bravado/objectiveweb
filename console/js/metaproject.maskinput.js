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
            // TODO verificar se value é Date ou String e configurar de acordo
            if (typeof(value) != 'number') {
                value = parseFloat(value) || 0;
            }

            $(element).trigger("money.update", [ value ]);
        }
    }
})(jQuery, ko);
// - end of mask/money input