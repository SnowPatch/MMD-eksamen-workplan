var select = function(id) { return document.querySelector(id) };
var selectAll = function(id) { return document.querySelectorAll(id) };
var json = function(data) { return JSON.stringify(data) };

function listen(selector, type, action) {
    select(selector).addEventListener(type, function(e){
        action(e);
    }, true);
}

function listenAll(selector, type, action) {
    var targets = selectAll(selector);
    for (var i=0,l=targets.length; i<l; i++) {
        targets[i].addEventListener(type, function(e){
            action(e);
        }, true);
    }
}

function post(obj) {
    var xhr = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                var data = JSON.parse(xhr.responseText);
            } catch (e) {
                var data = {error:e}
            }
            obj.done(data);
        }
    }
    xhr.open('POST', obj.url, true);
    xhr.setRequestHeader('Content-type', obj.type);
    xhr.send(json(obj.data));
}