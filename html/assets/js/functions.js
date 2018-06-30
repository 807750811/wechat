
// 修改个人签名
function update_personal_sign(sign){
	$.ajax({
        type:"POST",
        url:"interface/data.php/savePersonalSign",
        data:{uid:uid,sign:sign},
        datatype: "json",       
        success:function(res){
  			if(res != false){
  				layer.msg('修改成功!');
  			}else{
  				layer.msg('网络连接异常,修改失败!');
  			}      	
        },
        error:function(){
        	layer.msg('网络连接异常,修改失败!');
        }
    });
}

// 过滤字符串
function filter_string(string){
	string = string.replace( new RegExp('"','gm'),'&quot;' );
	string = string.replace( new RegExp(/\r\n/g),'<br>' );
	string = string.replace( new RegExp(/\n/g),'<br>' );
	return string;
}

// 恢复被过滤的字符串
function recover_string(string){
	string = string.replace( new RegExp('&amp;quot;','gm'),'"' );
	string = string.replace( new RegExp('&lt;br&gt;','gm'),'\r\n' );
	return string;
}

// 将键值对加入到指定数组中
function insert_into_jsonObject(jsonObject,key,value){
	jsonObject[key] = value;
	//return jsonObject;
}

// 将键值对从数组中移除
function delete_from_jsonObject(jsonObject,key){
	delete jsonObject[key];
	//return jsonObject;
}

// 删除所有cookie
function clearCookie(){
    var keys=document.cookie.match(/[^ =;]+(?=\=)/g);
    if (keys) {
        for (var i =  keys.length; i--;)
            document.cookie=keys[i]+'=0;expires=' + new Date( 0).toUTCString()
    }    
}

function check_login_status(uid){
	var guid;
	var tokenId = cookie.get("tokenId");
	if( tokenId != undefined ){
		var user_data = cookie.get(tokenId);
		if( user_data != undefined ){
			var user_data_obj = $.parseJSON(user_data);
			guid = user_data_obj['uid'];
		}
	}
	
	if( guid != undefined ){
		if( guid==uid ){
			return false;
		}else{
			return guid;
		}
	}else{
		return false;
	}
}


