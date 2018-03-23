var u = navigator.userAgent;
var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
if (u.indexOf(" U;") > -1) {
    isiOS = false;
}
var ua = navigator.userAgent.toLowerCase();
function isWeiXin() {
    var ua = window.navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == 'micromessenger') {
        return true;
    } else {
        return false;
    }
}

function show_bg() {
    document.getElementById("bg").style.display = "block"
}       
function show_jc() {
    document.getElementById("jc").style.display = "block"
}
function start_app() {
    if (isWeiXin()) {
        if (isiOS) {
            show_jc();
        }else{
            show_bg();
        }
        return;
    }
    if (isiOS) {
        //window.location = "itms-services://?action=download-manifest&url=https://niuxingyu.com/starsdownload/app_ios.plist";
        window.location = "http://fir.im/wcgb";
    } else if (isAndroid) {
        //window.location = "http://game.niuxingyu.com/download/app/stars_android_app.apk";
        window.location = "http://fir.im/sab";
        return;
    } else {
        //默认安卓
        //window.location = "http://game.niuxingyu.com/download/app/stars_android_app.apk";
        window.location = "http://fir.im/sab";
        return;
    }
}