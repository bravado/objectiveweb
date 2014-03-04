/**
 * Created by IntelliJ IDEA.
 * User: guigouz
 * Date: 13/05/11
 * Time: 01:58
 * To change this template use File | Settings | File Templates.
 */

function ObjectiveWeb(url) {
    var self = this;

    self.url = function(object) {
        object = object || '';
        if(object[0] != '/') {
            object = '/' + object;
        }

        return url + '/index.php' + object;
    };

    self.get = function(object, params) {
        return jQuery.ajax({
                url: self.url(object),
                data: params || {},
                dataType: 'json',
                type: 'GET'
            }
        );
    };

    self.post = function(object, data) {
        return jQuery.ajax({
            url: self.url(object),
            dataType: 'json',
            type: 'POST',
            data: data
        });
    };

    self.put = function(object, data) {
        return jQuery.ajax({
            url: self.url(object),
            dataType: 'json',
            type: 'PUT',
            data: data
        });
    };


    self['delete'] = function(object, data) {
        return jQuery.ajax({
            url: self.url(object),
            dataType: 'json',
            type: 'DELETE',
            data: data
        });
    };

}