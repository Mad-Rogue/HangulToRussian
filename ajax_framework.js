/* -------------------------- */
/*   XMLHTTPRequest Enable    */
/* -------------------------- */
function createObject() {
    var request_type;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        request_type = new ActiveXObject("Microsoft.XMLHTTP");
    } else {
        request_type = new XMLHttpRequest();
    }
    return request_type;
}

var http = createObject();
var b_sending = false;

function sendData() {
    if(b_sending){
        return;
    }
    document.getElementById('msg').style.display = "block";
    form_text = encodeURI(document.getElementById('hangultext').value);

    b_sending = true;
    document.getElementById('msg').innerHTML = '<img src="ajax-loader.gif " /><br />Идёт обработка запроса...';
    // Set te random number to add to URL request
    http.open('get', 'hangul.php?text='+form_text);
    http.onreadystatechange =  readyStat;
    http.send(null);
}
function readyStat() {
    if(http.readyState == 4){
        b_sending = false;
        var response = http.responseText;
        document.getElementById('msg').innerHTML = response;
    }
}
