/**
 * Created by IntelliJ IDEA.
 * User: guigouz
 * Date: 13/05/11
 * Time: 01:58
 * To change this template use File | Settings | File Templates.
 */

function ObjectiveWeb(url) {
    var ow = this;
    this.url = url + '/index.php';

    this.getUrl = function(object) {
        if(object[0] != '/') {
            object = '/' + object;
        }

        return ow.url + object;
    };
    this.get = function(object) {

        return jQuery.ajax({
                url: ow.getUrl(object),
                dataType: 'json',
                type: 'GET'
            }
        );
    };

    this.post = function(domain, data) {
        return jQuery.ajax({
            url: self.url,
            dataType: 'json',
            type: 'POST',
            data: data
        })
    };
    this.attach = function(object, file) {
        return jQuery.ajax({
            url: ow.getUrl(object) + '/' + file.name,
            type: 'PUT',
            contentType: file.type,
            data: file.data
        });
        
    };

}