
jQuery(document).ready(function() {
	
	// 判断cookie中保存的用户是否在本浏览器上正在登录,否则清除本浏览器上的cookie
	var tokenId = cookie.get("tokenId");
	var uid;
	if( tokenId != undefined ){
		var user_data = cookie.get(tokenId);
		if( user_data != undefined ){
			var user_data_obj = $.parseJSON(user_data);
			uid = user_data_obj['uid'];
		}
		if( uid != undefined ){
			$.ajax({
				type : "POST",
				url : "interface/data.php/checkUserLogin",
				data : {uid : uid},
				datatype : "json",
				success : function(data){
					var res = $.parseJSON(data);
					if( res.result == '1' ){
						// 已登录的用户不在本浏览器上登录
						if( tokenId != res.tokenId ){
							cookie.remove(tokenId);
							cookie.remove("tokenId");
						}
					}
				}
			});
		}
	}
	
    $('.page-container form button').click(function(){
        var username = $('.username').val();
        var password = $('.password').val();
        if(username == '') {
            $('.error').fadeOut('fast', function(){
                $(this).css('top', '27px');
            });
            $('.error').fadeIn('fast', function(){
                $('.username').focus();
            });
            return false;
        }
        if(password == '') {
            $('.error').fadeOut('fast', function(){
                $(this).css('top', '96px');
            });
            $('.error').fadeIn('fast', function(){
                $('.password').focus();
            });
            return false;
        }
        // 生成登录接口token
        var encrypted_data = JSON.stringify({"username":username,"password":hex_sha1(password),"action":"userLogin"});
        var encrypted_token = encrypted(encrypted_data);
		$.ajax({
            type:"POST",
            url:"interface/data.php/userLogin",
            data:{token:encrypted_token},
            datatype: "json",       
            success:function(data){
				var res = $.parseJSON(data);
				if(res.status == '-1'){
					alert(res.msg);
				}else if(res.status == '1'){
					// 获取当前浏览器上是否已有登录用户,获取其uid
					var login_status = check_login_status(res.user_data.uid);
					clearCookie();
					var tokenId = res.tokenId;
					var user_data = {
							'uid' : res.user_data.uid,
							'username' : res.user_data.username,
							'avatar' : res.user_data.avatar,
							'sign' : res.user_data.sign,
							'groups_list' : res.user_data.groups_list,
							'login_status' : login_status
					};
					cookie.set(tokenId,JSON.stringify(user_data));
					cookie.set('tokenId',tokenId);
					cookie.set('tokens',res.tokens);
					location.href = res.url;
				}  
            },
            error: function(){
				alert("网络连接异常");
            }         
         });
    });

    $('.page-container form .username, .page-container form .password').keyup(function(){
        $('.error').fadeOut('fast');
    });

});
