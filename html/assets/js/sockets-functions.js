WEB_SOCKET_SWF_LOCATION = "html/assets/swf/WebSocketMain.swf";
WEB_SOCKET_DEBUG = true;
var ws;
function connect(){
	ws = new WebSocket("ws://"+document.domain+":7272");
	ws.onopen = onopen;
	ws.onmessage = onmessage;
	ws.onclose = function(){
		connect();
	};
	ws.onerror = function(){};
}
// 创建socket链接
function onopen(){
	var login_data = '{"type":"init","uid":"'+uid+'","username":"'+username+'","avatar":"'+avatar+'","sign":"'
	+sign+'","groups_list":"'+groups_list+'","tokenId":"'+tokenId+'","login_status":"'+login_status+'"}';    
	ws.send(login_data);
	// 拉取离线时未读消息
	show_offline_message();
}
// 接收到数据时触发
function onmessage(e){
	var data = eval("("+e.data+")");
	// 接收聊天消息
	if( data.message_type == 'chatMessage'){
		var receive_data = data.data;
		receive_data.content = recover_string(receive_data.content);
		layim.getMessage(receive_data);
		// 接收到信息后做出回复表示已接收
		var answer_data = '{"type":"answer_is_read","chat_id":"'+receive_data.chatId+'","from_type":"'+receive_data.type+'","received_uid":"'+uid+'"}';
		ws.send(answer_data);
	}
	// 即时更新用户的在线状态(在线|隐身)
	else if( data.message_type == 'hide' || data.message_type == 'online' ){
		if(data.id!=uid){
			layim.setFriendStatus(data.id , data.message_type);
		}
	}
	// 更新离线用户状态
	else if( data.message_type == 'logout' ){
		layim.setFriendStatus(data.id , data.message_type);
		// 移除当前用户聊天窗口的输入状态
		if( chat_object_now != undefined ){
			if( chat_object_now.data.id == data.id ){
				layim.setChatStatus('');
			}
		}
		delete_from_jsonObject(users_chat_status,data.id);
	}
	// 更新对方用户状态是否正在输入
	else if( data.message_type == 'chatStatus' ){
		// 对方正在输入,设置其状态,并将对方uid加入到记录数组中
		if(data.status == 'on'){
			if( chat_object_now != undefined ){
				if( chat_object_now.data.id == data.id ){
					layim.setChatStatus('<span style="color:#FF5722;">对方正在输入。。。</span>');
				}
			}
			insert_into_jsonObject(users_chat_status,data.id,1);
		}
		// 对方停止正在输入,取消其状态,并将对方uid从记录数组中移除
		else if(data.status == 'off'){
			if( chat_object_now != undefined ){
				if( chat_object_now.data.id == data.id ){
					layim.setChatStatus('');
				}
			}
			delete_from_jsonObject(users_chat_status,data.id);
		}
	}
	// 多端登录时剔除前者登录端
	else if( data.message_type == 'other_login' ){
		cookie.remove( data.tokenId );
		location.href = 'login.html';
	}	
	// 消息提示推送
	else if( data.message_type == 'msg_notice' ){
		layim.msgbox(1);
	}
	// 好友添加成功推送
	else if( data.message_type == 'friend_add_success' ){
		var user_data = data.user_data;
		layim.msgbox(1);
		layim.addList({
      		type: 'friend'
      		,avatar: user_data.avatar 
      		,username: user_data.username 
      		,groupid: user_data.group 
      		,id: user_data.uid 
      		,sign: user_data.sign 
    	});
	}
	// 群组添加成功推送
	else if( data.message_type == 'group_add_success' ){
		var group_data = data.group_data;
		layim.msgbox(1);
		layim.addList({
			type : 'group'
			,avatar : group_data.group_avatar
			,groupname : group_data.group_name
			,id : group_data.group_id
		});
	}
	// 推送群组添加新用户系统消息
	else if( data.message_type == 'group_system_message' ){
		var message_data = data.message_data;
		layim.getMessage({
			system:true
			,id : message_data.group_id
			,type : "group"
			,content : message_data.content
		});
	}
	/*---------------------------------------------------------*/
	// 邀请对方进行游戏
	else if( data.message_type == 'game_invitation' ){
		var from_uid = data.from_uid;
		layer.open({
			type : 0,
			title : "确认进入游戏",
			content : "是否同意进入游戏？",
			id:"gameInvitationTo",
			cancel:function(index,layero){
				if(confirm('是否要关闭进入游戏呢？')){
					var send_data = '{"type":"game_invitation_refuse","from_uid":"'+from_uid+'"}';
					ws.send(send_data);
					layer.close(index);
				}
				return false;
			},
			yes:function(index,layero){
				var send_data = '{"type":"game_invitation_receive","from_uid":"'+from_uid+'","to_uid":"'+uid+'"}';
				ws.send(send_data);
				layer.close(index);
			}
		});
	}
	// 被邀请方不在线时的提示
	else if( data.message_type == 'game_invitation_fail' ){
		layer.alert('对方不在线，不能邀请对方游戏！',function(index){
			var invitation_index = layer.getFrameIndex("gameInvitationFrom");
			layer.close(invitation_index);
			layer.close(index);
		});
	}
	// 被邀请方正在游戏时提示
	else if( data.message_type == 'competitor_is_onGame' ){
		layer.alert('对方正在游戏当中，不能邀请对方游戏！',function(index){
			var invitation_index = layer.getFrameIndex("gameInvitationFrom");
			layer.close(invitation_index);
			layer.close(index);
		});
	}
	// 不能同时多邀请用户游戏的提示
	else if( data.message_type == 'inviter_is_onGame' ){
		layer.alert('您正在游戏当中，不能邀请对方游戏！',function(index){
			var invitation_index = layer.getFrameIndex("gameInvitationFrom");
			layer.close(invitation_index);
			layer.close(index);
		});
	}
	// 本方取消游戏时提示
	else if( data.message_type == 'game_invitation_cancel' ){
		layer.alert('对方取消了游戏邀请',function(index){
			var invitation_index = layer.getFrameIndex("gameInvitationTo");
			layer.close(invitation_index);
			layer.close(index);
		});
	}
	// 对方拒绝你的邀请时提示
	else if( data.message_type == 'game_invitation_refuse' ){
		layer.alert('对方拒绝了你的邀请',function(index){
			var invitation_index = layer.getFrameIndex("gameInvitationFrom");
			layer.close(invitation_index);
			layer.close(index);
		});
	}
	// 接受邀请时进入游戏界面
	else if( data.message_type == 'game_invitation_receive' ){
		var competitor_uid = data.competitor;
		cookie.set("competitor_uid",competitor_uid);
		cookie.set("playerRole",data.playerRole)
		layer.close(layer.index);
		layer.open({
			  type:2,
			  title:"游戏",
			  id:'gameFrame',
			  shadeClose:true,
			  shade:false,
			  maxmin:true,
			  area: ['650px', '750px'],
			  content:'html/modules/gameFrame.html',
			  cancel:function(index,layero){
				  if(confirm('是否要退出游戏呢？')){
					  layer.close(index);
				  }
				  return false;
			  },
			  end:function(){
				  cookie.remove("competitor_uid");
				  cookie.remove("playerRole");
				  $(window).unbind('beforeunload');
			  },
			  success:function(layero,index){
				  // 当正在游戏时绑定刷新页面提示
				  $(window).bind('beforeunload',function(){
					  return '关闭浏览器将被视为退出游戏';
				  });
			  }
	    });
	}
	// 对方退出游戏时提示
	else if( data.message_type == 'game_logout' ){
		layer.alert('对方退出了游戏');
		layer.closeAll('iframe');
		cookie.remove("competitor_uid");
	}
	
}

// 拉取离线时未读信息
function show_offline_message(){
	var href = "interface/data.php/getOfflineMessage";
	$.ajax({
		type : 'post',
		url : href,
		data : { uid:uid },
		datatype : "json",
		success : function(data){
			var res = $.parseJSON(data);
			$.each(res,function(key,value){
				layim.getMessage(value);
				// 接收到信息后做出回复表示已接收
				var answer_data = '{"type":"answer_is_read","chat_id":"'+value.chatId+'","from_type":"'+value.type+'","received_uid":"'+uid+'"}';
				ws.send(answer_data);
			});
		}
	});
}

