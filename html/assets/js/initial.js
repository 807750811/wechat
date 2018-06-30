// 定义layim对象
var layim;
//----------聊天状态定义对象------------//
// 定义记录用户聊天状态(对方正在输入的用户)
var users_chat_status = {};
// 本地记录用户聊天状态(正在向对象输入的用户)
var users_chat_status_local = {};
// 当前切换着的聊天窗口对象
var chat_object_now;
// 记录信息盒未读信息数
var msg_count = '0';
// 记录子窗口内容的修改状态,用户判断关闭该子窗口是否刷新主页面
var child_window_change = false;

// 获取用户uid,若无登录跳转到登录界面
var tokenId = cookie.get("tokenId");
if( tokenId != undefined ){
	var user_data = cookie.get(tokenId);
	var user_data_obj = $.parseJSON(user_data);
	// 获取cookie中记录的用户的信息
	var uid = user_data_obj['uid'];
	var username = user_data_obj['username'];
	var avatar = user_data_obj['avatar'];
	var sign = user_data_obj['sign']
	var groups_list = user_data_obj['groups_list'];
	var login_status = user_data_obj['login_status'];
	
	if(uid == undefined){
		location.href = 'login.html';
	}
}else{
	location.href = 'login.html';
}

jQuery(document).ready(function() {
	$.ajax({
		type:"GET",
		url:"interface/data.php/getUnreadMessage/"+uid,
		datatype: "json", 
		success : function(data){
			var res = $.parseJSON(data);
			if( res.status == '1' ){
				msg_count = res.result;
			}
		}
	});
});