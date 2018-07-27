#1. 登录
接口：https://game.igccc.com/index.php/index/index/login
传参：
type:1  //手机号登录
phone:13007686112
password:123456
from:wx     //登录源，wx  ios  android  h5

#2. 进入服务器
var wsServer = 'ws://igccc.com:9502?token=c4ca4238a0b923820dcc509a6f75849b&from=wx';
var websocket = new WebSocket(wsServer);
websocket.onopen = function (evt) {
    console.log("Connected to WebSocket server.");
};

websocket.onclose = function (evt) {
    console.log("Disconnected");
};

websocket.onmessage = function (evt) {
    console.log('Retrieved data from server: ' + evt.data);
};

websocket.onerror = function (evt, e) {
    console.log('Error occured: ' + evt.data);
};

#3. 选择场次
websocket.send(JSON.stringify({"mode":"selectroom","room":"2"}));

#4. 开始匹配

