// Rich Text Editor
// Depends on tinymce, options are passed via the tinymceOptions binding
// Binding structure taken from http://jsfiddle.net/rniemeyer/BwQ4k/
(undefined != window.tinymce) && (function ($, ko, tinymce) {
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
                element.id = 'mp_rte_' + new Date().getTime();
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
    }
})(jQuery, ko, tinymce);
// - end of rich text editor