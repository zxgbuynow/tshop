var conn = new WebIM.connection({
	    isMultiLoginSessions: WebIM.config.isMultiLoginSessions,
	    https: typeof WebIM.config.https === 'boolean' ? WebIM.config.https : location.protocol === 'https:',
	    url: WebIM.config.xmppURL,
	    heartBeatWait: WebIM.config.heartBeatWait,
	    autoReconnectNumMax: WebIM.config.autoReconnectNumMax,
	    autoReconnectInterval: WebIM.config.autoReconnectInterval,
	    apiUrl: WebIM.config.apiURL,
	    isAutoLogin: true
	});
conn.listen({ 
    onOpened: function ( message ) {          //连接成功回调
        // 如果isAutoLogin设置为false，那么必须手动设置上线，否则无法收消息
        // 手动上线指的是调用conn.setPresence(); 如果conn初始化时已将isAutoLogin设置为true
        // 则无需调用conn.setPresence();        
    },  
    onClosed: function ( message ) {},         //连接关闭回调
    onTextMessage: function ( message ) {
    		console.log(JSON.stringify(message))
    		mui.toast('new msg')
//		console.log('-------------111-----------------')
    		//TODO 处理缓存
    		setImMsg('receiver','text', message.data, message.from, message.to);
    		
    		//处理刷新会话
    		if (window.plus) {
//  			mui.toast('falg')
        		if(plus.webview.currentWebview().id == '_www/view/msg/index.html'){
//      			plus.webview.currentWebview().reload()
				var msgind = plus.webview.getWebviewById('_www/view/msg/index.html');
				mui.fire(msgind, 'refreshmsglist');
        		}
//      		log(plus.webview.currentWebview())
//              		plus.webview.currentWebview().reload()
        }
    	},    //收到文本消息
    onEmojiMessage: function ( message ) {},   //收到表情消息
    onPictureMessage: function ( message ) {}, //收到图片消息
    onCmdMessage: function ( message ) {},     //收到命令消息
    onAudioMessage: function ( message ) {},   //收到音频消息
    onLocationMessage: function ( message ) {},//收到位置消息
    onFileMessage: function ( message ) {},    //收到文件消息
    onVideoMessage: function (message) {
        var node = document.getElementById('privateVideo');
        var option = {
            url: message.url,
            headers: {
              'Accept': 'audio/mp4'
            },
            onFileDownloadComplete: function (response) {
                var objectURL = WebIM.utils.parseDownloadResponse.call(conn, response);
                node.src = objectURL;
            },
            onFileDownloadError: function () {
                console.log('File down load error.')
            }
        };
        WebIM.utils.download.call(conn, option);
    },   //收到视频消息
    onPresence: function ( message ) {
    		handlePresence(message)
    		console.log(JSON.stringify(message))
    	},       //处理“广播”或“发布-订阅”消息，如联系人订阅请求、处理群组、聊天室被踢解散等消息
    onRoster: function ( message ) {mui.toast('收到好友申请信息')},         //处理好友申请
    onInviteMessage: function ( message ) {},  //处理群组邀请
    onOnline: function () {},                  //本机网络连接成功
    onOffline: function () {},                 //本机网络掉线
    onError: function ( message ) {},          //失败回调
    onBlacklistUpdate: function (list) {       //黑名单变动
        // 查询黑名单，将好友拉黑，将好友从黑名单移除都会回调这个函数，list则是黑名单现有的所有好友信息
        console.log(list);
    },
    onReceivedMessage: function(message){},    //收到消息送达服务器回执
    onDeliveredMessage: function(message){},   //收到消息送达客户端回执
    onReadMessage: function(message){},        //收到消息已读回执
    onCreateGroup: function(message){},        //创建群组成功回执（需调用createGroupNew）
    onMutedMessage: function(message){}        //如果用户在A群组被禁言，在A群发消息会走这个回调并且消息不会传递给群其它成员
});

////初始化语音
//var rtcCall = new WebIM.WebRTC.Call({
//  connection: conn,
//  mediaStreamConstaints: {
//          audio: true,
//          video: true
//  },
//
//  listener: {
//      onAcceptCall: function (from, options) {
//          console.log('onAcceptCall::', 'from: ', from, 'options: ', options);
//      },
//      //通过streamType区分视频流和音频流，streamType: 'VOICE'(音频流)，'VIDEO'(视频流)
//      onGotRemoteStream: function (stream, streamType) {
//          console.log('onGotRemoteStream::', 'stream: ', stream, 'streamType: ', streamType);
//          var video = document.getElementById('video');
//          video.srcObject = stream;
//      },
//      onGotLocalStream: function (stream, streamType) {
//          console.log('onGotLocalStream::', 'stream:', stream, 'streamType: ', streamType);
//          var video = document.getElementById('localVideo');
//          video.srcObject = stream;
//      },
//      onRinging: function (caller) {
//          console.log('onRinging::', 'caller:', caller);
//      },
//      onTermCall: function (reason) {
//          console.log('onTermCall::');
//          console.log('reason:', reason);
//      },
//      onIceConnectionStateChange: function (iceState) {
//          console.log('onIceConnectionStateChange::', 'iceState:', iceState);
//      },
//      onError: function (e) {
//          log(e);
//      }
//   }
//});
//
//// 视频呼叫对方
//var webvcall = function (call, reciver) {
//  rtcCall.caller = call;
//  rtcCall.makeVideoCall(reciver);
//};
//// 音频呼叫对方
//var webscall = function (call, reciver) {
//  rtcCall.caller = call;
//  rtcCall.makeVoiceCall(reciver);
//};
//// 关掉/拒绝视频
//var endCall = function () {
//  rtcCall.endCall();
//}
//// 接受对方呼叫
//var acceptCall = function () {
//  rtcCall.acceptCall();
//}
//好友申请
var handlePresence = function(e) {
//  mui.toast(JSON.stringify(e));
    
    var user = e.from;
    //（发送者希望订阅接收者的出席信息）
    if (e.type == 'subscribe') {
        mui.confirm('有人要添加你为好友', '添加好友', ['确定','取消'], function(e){
            if (e.index == 0) {
                //同意添加好友操作的实现方法
                conn.subscribed({
                    to : user,
                    message : "[resp:true]"
                });
                if (window.plus) {
                		if(plus.webview.currentWebview().id == '_www/view/msg/group.html'){
                			plus.webview.currentWebview().reload()
                		}
                		log(plus.webview.currentWebview())
//              		plus.webview.currentWebview().reload()
                }
                mui.toast('你同意添加好友请求');
            } else {
                //拒绝添加好友的方法处理
                conn.unsubscribed({
                    to : user,
                    message : "rejectAddFriend"
                });
                mui.toast('你拒绝了添加好友');
            }
        })
    }
};

var setImMsg = function(who, type, msg, from, to){
	//查看是否有历史消息
		var msgbox = plus.storage.getItem(to+"msgbox_"+from);
		//头像
		var avarlist = plus.storage.getItem("avarList");
		if(avarlist){
			avarlist = JSON.parse(avarlist)
			for (var i in avarlist) {
				if(avarlist[i]['name']==to){
					var receiverAvatar = avarlist[i]['avar']||'../../img/widgets_02_19.png';
				}
				if(avarlist[i]['name']==from){
					var senderAvatar = avarlist[i]['avar']||'../../img/widgets_02_19.png';
				}
			}
		}

		if(msgbox){
			msgbox = objToArrayim(JSON.parse(msgbox));
			msgbox.push({
				who:who,
				type:type,
				body:{
			        senderAvatar: senderAvatar||'../../img/widgets_02_19.png',
			        receiverAvatar: receiverAvatar||'../../img/widgets_02_19.png',
			        msg: msg
			    }
			})
		}else{
			msgbox = [];
			msgbox.push({
				who:who,
				type:type,
				body:{
			        senderAvatar: senderAvatar||'../../img/widgets_02_19.png',
			        receiverAvatar: receiverAvatar||'../../img/widgets_02_19.png',
			        msg: msg
			    }
			}) 
		}
	    
	    plus.storage.setItem(to+"msgbox_"+from,JSON.stringify(msgbox));
	    //处理未读
		plus.storage.setItem(to+"msgbox_"+from+'_flag','yes');
}
function objToArrayim(array) {
    var arr = []
    for (var i in array) {
        arr.push(array[i]); 
    }
//  console.log(arr);
    return arr;
}