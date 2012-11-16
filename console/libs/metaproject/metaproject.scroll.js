(function($, ko) {

    ko.bindingHandlers.scroll = {
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
    }
})(jQuery, ko);