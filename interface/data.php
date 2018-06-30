<?php 

require 'Slim/Slim.php';
require 'Medoo/Medoo.php'; 
require 'functions.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

// 获取个人信息
$app->get('/getPersonalInfo/:uid','getPersonalInfo');

$app->get('/getUsernameByUid/:uid','getUsernameByUid');

$app->post('/getGameResult/','getGameResult');

// 获取群组成员
$app->get('/getGroupMembers/','getGroupMembers');

// 获取消息盒信息列表
$app->get('/getMsgBoxInfo/:uid','getMsgBoxInfo');

// 获取未读消息数
$app->get('/getUnreadMessage/:uid','getUnreadMessage');

// 设置已读消息
$app->get('/setReadMessage/:uid','setReadMessage');

// 同意添加好友
$app->post('/agreeFriend/','agreeFriend');

// 拒绝添加好友
$app->post('/refuseFriend/','refuseFriend');

// 同意添加群组
$app->post('/agreeGroup/','agreeGroup');

// 拒绝添加群组
$app->post('/refuseGroup/','refuseGroup');

// 查找用户
$app->post('/findFriends/','findFriends');

// 查找群组
$app->post('/findGroups/','findGroups');

// 申请添加好友
$app->post('/applyForFriends/','applyForFriends');

// 登录接口
$app->post('/userLogin/','userLogin');

// 获取用户的详细信息
$app->post('/userDetailInfo/','userDetailInfo');

// 获取好友分组列表
$app->post('/userGroupList/','userGroupList');

// 保存用户的详细信息
$app->post('/saveUserDetailInfo/','saveUserDetailInfo');

// 移除好友分组
$app->post('/removeUserGroup/','removeUserGroup');

// 检查cookie中记录的用户是否在线
$app->post('/checkUserLogin/','checkUserLogin');

// 修改个人签名
$app->post('/savePersonalSign/','savePersonalSign');

// 图片上传
$app->post('/uploadImages/','uploadImages');

// 文件上传
$app->post('/uploadFiles/','uploadFiles');

// 头像上传
$app->post('/uploadAvatar/:uid','uploadAvatar');

// 接收离线消息
$app->post('/getOfflineMessage/','getOfflineMessage');

// 获取历史聊天消息
$app->post('/getHistoryChat/','getHistoryChat');

// 保存群组成员与群组的对应关系到redis的哈希表中
$app->get('/saveGroupMemberList/','saveGroupMemberList');

$app->post('/decodeTokens/','decodeTokens');

// 启动运行
$app->run();

// 获取个人信息及好友/群组列表
function getPersonalInfo($uid){
    $database = getDatabase();
    $result = array(
        'code' => '0',
        'msg' => '',
        'data' => array(
            'mine' => '',
            'friend' => '',
            'group' => ''
        )
    );
    // 个人信息
    $mine = $database->get("users", 
        ["uid(id)","nickname(username)","signature(sign)","avatar","groups_list"],
        ["uid" => $uid] );
    $mine['status'] = "online";
    // 聊天群
    $groups_list = $database->select("groups",
        ["group_id(id)" , "group_name(groupname)" , "group_avatar(avatar)"],
        ["group_id" => json_decode($mine['groups_list'],true)] );
    // 分组好友
    $friend_tags = $database->select("friend_tag",
        ["tag_id(id)" , "tag_name(groupname)" ,"list"],
        ["belongs_uid" => $mine['id'] , "ORDER" => ["non_deleted" => "DESC"] ] );
    // 循环判断好友是否在线
    foreach($friend_tags as $key=>$value){
        $friends = $database->select("users", 
        ["uid(id)","nickname(username)","signature(sign)","avatar"],
        ["uid"=> json_decode($value['list'],true) ] );
        $friend_tags[$key]['list'] = check_user_status($friends,$mine['id']);
    }
    $result['data']['mine'] = $mine;
    $result['data']['friend'] = $friend_tags;
    $result['data']['group'] = $groups_list;
    echo json_encode($result);
}

function getUsernameByUid($uid){
    $database = getDatabase();
    $result = array(
        'code' => '0',
        'msg'  => '',
        'username' => ''
    );
    $getUsername = $database->get("users",
        ["nickname(username)"],["uid"=>$uid]);
    $result['username'] = $getUsername['username'];
    echo json_encode($result);
}

// 获取对战结果信息
function getGameResult(){
    $database = getDatabase();
    $result = array(
        'code' => '0',
        'msg'  => '',
        'mine' => '',
        'competitor' => ''
    );
    $mine = $database->get("game_result",
        ["win_times","lose_times","escape_times"],
        ["uid"=>intval($_POST['uid'])]);
    $mine_total = $mine['win_times'] + $mine['lose_times'] + $mine['escape_times'];
    $result['mine'] = array(
        'win'=>$mine['win_times'],
        'lose'=>$mine['lose_times'],
        'escape'=> round(($mine['escape_times'] / $mine_total)*100)."%",
        'total'=>$mine_total
    );
    $competitor = $database->get("game_result",
        ["win_times","lose_times","escape_times"],
        ["uid"=>intval($_POST['competitor_uid'])]);
    $competitor_total = $competitor['win_times'] + $competitor['lose_times'] + $competitor['escape_times'];
	$competitor_total = $competitor_total!=0 ? $competitor_total:0;
    $result['competitor'] = array(
        'win'=>$competitor['win_times'],
        'lose'=>$competitor['lose_times'],
        'escape'=> round(($competitor['escape_times'] / $competitor_total)*100)."%",
        'total'=>$competitor_total
    );
    echo json_encode($result);
}

// 获取群组组员列表
function getGroupMembers(){
    // 获取群组id
    $gid = $_GET['id'];
    $database = getDatabase();
    $result = array(
        'code' => '0',
        'msg' => '',
        'data' => array(
            'list' => ''
        )
    );
    // 获取群组成员
    $member_list = $database->get("groups","member_list",["group_id"=>$gid]);
    $members = $database->select("users",
        ["uid(id)" , "nickname(username)" , "signature(sign)" , "avatar(avatar)"],
        ["uid" => json_decode($member_list) ] );
    $result['data']['list'] = $members;
    echo json_encode($result);
}

// 获取消息盒信息列表
function getMsgBoxInfo($uid){
    $database = getDatabase();
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    // 接收人端消息模板
    $msg_type = array(
        '2' => '{$username} 拒绝了你的好友申请',
        '3' => '{$username} 已经同意你的好友申请',
        '5' => '{$username} 拒绝了你加入 {$group} 的申请',
        '6' => '{$username} 已经同意你加入 {$group} 的申请'
    );
    // 申请人端消息模板
    $msg_type_extra = array(
        '1' => '{$username} 申请添加你为好友',
        '2' => '你拒绝了 {$username} 的好友申请',
        '3' => '你同意了 {$username} 的好友申请',
        '4' => '{$username} 申请加入 {$group} 群组',
        '5' => '你拒绝了 {$username} 加入 {$group} 的申请',
        '6' => '你同意了 {$username} 加入 {$group} 的申请'
    );
    $msg_list = $database->select( "msg_box",
        ["msg_id(id)" , "type" , "uid" , "from_uid(from)" , "from_group" , "remark" , "addtime(time)" ,"my_is_read(read)" ,"group_id","add_type"],
        [ "OR" =>[ "uid" => intval($uid) ,"AND" =>[ "from_uid" => intval($uid) , "type[!]"=>['1','4'] ] ] , 
        "ORDER" => ["addtime" => "DESC","type"=>"ASC"],
        "LIMIT" => [ ($page-1)*6 , 6 ] ] );
    $msg_list_count = $database->count("msg_box",["OR"=> ["uid"=>intval($uid) , "AND"=>[ "from_uid"=>intval($uid) , "type[!]"=>['1','4'] ] ] ]);
    // 分页页数
    $pages = intval($msg_list_count / 6) + 1;
    foreach ( (array)$msg_list as $key=>$value ){
        if( $value['uid'] != $uid ){ // 接收人端模板替换
            $username = $database->get("users","nickname",["uid"=>$value['uid']]);
            if( $value['add_type'] == '1' ){  // 添加好友
                $msg_list[$key]['content'] = str_replace('{$username}',$username,$msg_type[$value['type']]);
            }else{  // 添加群组
                $groupname = $database->get("groups","group_name",["group_id"=>$value['group_id']]);
                $temp = str_replace('{$username}', $username, $msg_type[$value['type']]);
                $msg_list[$key]['content'] = str_replace('{$group}', $groupname, $temp);
            }
        }else{  // 申请人端模板替换
            $username = $database->get("users","nickname",["uid"=>$value['from']]);
            if( $value['add_type'] == '1' ){  // 添加好友
                $msg_list[$key]['content'] = str_replace('{$username}',$username,$msg_type_extra[$value['type']]);
            }else{  // 添加群组
                $groupname = $database->get("groups","group_name",["group_id"=>$value['group_id']]);
                $temp = str_replace('{$username}', $username, $msg_type_extra[$value['type']]);
                $msg_list[$key]['content'] = str_replace('{$group}', $groupname, $temp);
            }
        }
        // 带上申请人信息
        $user = $database->get( "users" , 
            ["uid(id)","avatar","nickname(username)","signature(sign)"] ,
            ["uid" => $value['from']] );
        $msg_list[$key]['user'] = $user;
        // 如果是添加群组则带上群组信息
        if( $value['add_type'] == '2' ){
            $group = $database->get( "groups",
                ["group_id","group_name","group_avatar"] ,
                ["group_id" => $value['group_id']] );
            $msg_list[$key]['group'] = $group;
        }
    }
    $result = array(
        'code' => '0',
        'pages' => $pages,
        'data' => $msg_list
    );
    echo json_encode($result);
}

// 获取未读消息数目
function getUnreadMessage($uid){
    $database = getDatabase();
    $uid = intval($uid);
    $sql = "SELECT count(msg_id) FROM msg_box WHERE ( uid = '$uid' AND my_is_read = '0' ) ".
        " OR ( from_uid = '$uid' AND from_is_read = '0' AND type != '1' ) ";
    $count = $database->query($sql)->fetch();
    $result = array(
        "status" => "1",
        "result" => $count[0]
    );
    echo json_encode($result);
}

// 设置已读消息
function setReadMessage($uid){
    $database = getDatabase();
    $uid = intval($uid);
    $database->update("msg_box",["my_is_read"=>'1'],[ "uid" => $uid , "my_is_read"=> '0' ]);
    $database->update("msg_box",["from_is_read"=>'1'],[ "from_uid"=>$uid , "from_is_read"=>'0', "type[!]"=>'1' ]);
}

// 同意添加好友
function agreeFriend(){
    $database = getDatabase();
    // 开启事务
    $database->action(function($database){
        $result = array('code'=>'0');
        
        $msg_id = intval($_POST['msg_id']);
        $my_group = intval($_POST['group']);
        // 修改信息状态
        $res = $database->update('msg_box',["type"=>3],["msg_id"=>$msg_id]);
        $msg_info = $database->get('msg_box',["uid","from_uid","from_group"],["msg_id"=>$msg_id]);
        // 我添加对方为好友操作
        $my_friend_tag = $database->get("friend_tag","list",["tag_id"=>$my_group]);
        $my_friend_tag = push_data_to_json($my_friend_tag, intval($msg_info['from_uid']));
        $res1 = $database->update("friend_tag",["list"=> $my_friend_tag ],["tag_id"=>$my_group]);
        // 对方添加我为好友操作
        $from_friend_tag = $database->get("friend_tag","list",["tag_id"=>$msg_info['from_group']]);
        $from_friend_tag = push_data_to_json($from_friend_tag, intval($msg_info['uid']));
        $res2 = $database->update("friend_tag",["list"=>$from_friend_tag],["tag_id"=>$msg_info['from_group']]);
        // 修改失败则回滚
        if( $res == FALSE || $res1 == FALSE || $res2 == FALSE ){
            $result['code'] = '-1';
            $result['msg'] = '网络异常,操作失败!';
            return FALSE;
        }
        
        echo json_encode($result);
    });
}

// 同意添加群组
function agreeGroup(){
    $database = getDatabase();
    // 开启事务
    $database->action(function($database){
        $result = array('code'=>'0');
        
        $msg_id = intval($_POST['msg_id']);
        $from_uid = intval($_POST['from_uid']);
        $group_id = intval($_POST['group_id']);
        // 修改信息状态
        $res = $database->update("msg_box",["type"=>6],["msg_id"=>$msg_id]);
        // 加入群组操作
        $my_group_list = $database->get("users","groups_list",["uid"=>$from_uid]);
        $my_group_list = push_data_to_json($my_group_list, $group_id);
        $res1 = $database->update("users",["groups_list"=>$my_group_list],["uid"=>$from_uid]);
        $groups_list = $database->get("groups","member_list",["group_id"=>$group_id]);
        $groups_list = push_data_to_json($groups_list, $from_uid);
        $res2 = $database->update("groups",["member_list"=>$groups_list],["group_id"=>$group_id]);
        // 修改失败则回滚
        if( $res == FALSE || $res1 == FALSE || $res2 == FALSE ){
            $result['code'] = '-1';
            $result['msg'] = '网络异常,操作失败!';
            return FALSE;
        }else{
            // 更新redis中保存的组员列表
            $redis = getRedis();
            $redis->hSet("group_member_list",$group_id,$groups_list);
        }
        
        echo json_encode($result);
    });
}

// 拒绝添加好友
function refuseFriend(){
    $database = getDatabase();
    $result = array('code' => '0');
    $msg_id = intval($_POST['msg_id']);
    $res = $database->update('msg_box', ["type"=>2],["msg_id"=>$msg_id]);
    if( $res == FALSE ){
        $result['code'] = '-1';
        $result['msg'] = '网络异常,操作失败!';
    }
    echo json_encode($result);
}

// 拒绝添加加入群组
function refuseGroup(){
    $database = getDatabase();
    $result = array('code' => '0');
    $msg_id = intval($_POST['msg_id']);
    $res = $database->update('msg_box', ["type" => 5],["msg_id"=>$msg_id]);
    if( $res == FALSE ){
        $result['code'] = '-1';
        $result['msg'] = '网络异常,操作失败!';
    }
    echo json_encode($result);
}

// 查找好友
function findFriends(){
    $database = getDatabase();
    $uid = intval($_POST['uid']); // 申请人uid
    $keywords = $_POST['keywords']; // 查询关键词
    $search_type = intval($_POST['type']); // 查询类型 1-用户;2-群组
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;   // 分页页码
    if( $search_type == '1' ){  // 查询用户
        $data = $database->select("users", 
            ["uid","nickname(username)","signature(sign)","avatar"],
            [ "AND" => ["nickname[~]"=>$keywords , "uid[!]"=>$uid ],"LIMIT"=> [ ($page-1)*6 , 6 ] ]);
        // 标记查询结果中是否是已加好友
        foreach($data as $key=>$value){
            if( check_is_friend($uid, $value['uid']) ){
                $data[$key]['is_friend'] = 1;
            }else{
                $data[$key]['is_friend'] = 0;
            }
            $data[$key]['type'] = "users";
        }
        $count = $database->count("users",["nickname[~]"=>$keywords]);
    }else{  // 查询群组
        $data = $database->select( "groups",
            ["group_id(gid)","group_name","group_avatar","member_list","creator"],
            [ "group_name[~]"=>$keywords , "LIMIT"=> [ ($page-1)*6 , 6 ] ] );
        foreach( $data as $key=>$value ){
            if( check_is_in_group($uid, $value['member_list']) ){
                $data[$key]['in_group'] = 1;
            }else{
                $data[$key]['in_group'] = 0;
            }
            $data[$key]['creator_name'] = $database->get("users","nickname",["uid"=>$value['creator']]);
            $data[$key]['type'] = "groups";
        }
        $count = $database->count("group",["group_name[~]"=>$keywords]);
    }
    $pages = intval($count / 6) + 1;
    $result = array(
        'code'=>'0',
        'data' => $data,
        'msg' => '',
        'pages' => $pages
    );
    echo json_encode($result);
}

// 查找群组
function findGroups(){
    $database = getDatabase();
    $uid = intval($_POST['uid']);
    $keywords = $_POST['keywords'];
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $data = $database->select( "groups",
        ["group_id(gid)","group_name","group_avatar","member_list"],
        [ "group_name[~]"=>$keywords , "LIMIT"=> [ ($page-1)*6 , 6 ] ] );
    foreach( $data as $key=>$value ){
        if( check_is_in_group($uid, $value['member_list']) ){
            $data[$key]['in_group'] = 1;
        }else{
            $data[$key]['in_group'] = 0;
        }
    }
    $count = $database->count("group",["group_name[~]"=>$keywords]);
    $pages = intval( $count / 6 ) + 1;
    $result = array(
        'code' => '0',
        'data' => $data,
        'msg' => '',
        'pages' => $pages
    );
    echo json_encode($result);
}

// 申请添加好友
function applyForFriends(){
    $database = getDatabase();
    $my_uid = intval($_POST['my_uid']); // 接受人ID
    $to_uid = intval($_POST['to_uid']); // 申请人ID
    $remark = $_POST['remark']; // 申请备注
    $type = intval($_POST['type']); // 申请类型
    // 好友添加申请
    if( $type == '1' ){ 
        $group = intval($_POST['group']);
        // 申请添加好友时查询对方是否正向我添加好友申请
        $check = $database->get( "msg_box","msg_id",["type"=>'1',"uid"=>$my_uid,"from_uid"=>$to_uid] );
        if( $check != FALSE ){
            echo json_encode( array( 'code' => '-1' , 'msg' => '对方已向您申请为好友，请您到信息盒中进行确认' ) );exit;
        }
        
        $check = $database->get( "msg_box","msg_id",["type"=>'1',"uid"=>$to_uid,"from_uid"=>$my_uid] );
        // 若已经添加了申请且对方未确认,则不插入新数据
        if( $check == FALSE ){
            $res = $database->insert("msg_box", 
                ["type"=>'1',"uid"=>$to_uid,"from_uid"=>$my_uid,"from_group"=>$group,"remark"=>$remark,"addtime"=>date("Y-m-d H:i:s",time()),"add_type"=>'1'] );
            if($res == FALSE){
                echo json_encode( array( 'code' => '-1' , 'msg' => '网络异常,操作失败!' ) );exit;
            }
        }
    }
    // 群组添加申请
    else{
        $group_id = intval($_POST['group_id']);
        $check = $database->get( "msg_box","msg_id",["type"=>'4',"from_uid"=>$my_uid,"group_id"=>$group_id] );
        if( $check == FALSE ){
            $res = $database->insert("msg_box",
                ["type"=>'4',"uid"=>$to_uid,"from_uid"=>$my_uid,"remark"=>$remark,"addtime"=>date("y-m-d H:i:s",time()),"group_id"=>$group_id,"add_type"=>'2']);
            if($res == FALSE){
                echo json_encode( array( 'code' => '-1' , 'msg' => '网络异常,操作失败!' ) );exit;
            }
        }
    }
    $result = array( 'code' => 0 , 'msg' => '' );
    echo json_encode($result);
}

// 用户登录
function userLogin(){
    $result = array(
        'status' => '0',
        'msg' => '',
        'url' => '',
        'user_data' => ''
    );
    // 解密获取登录信息
    $post_data = decrypt(trim($_POST['token']));
    $username = $post_data['username'];
    $passwprd = $post_data['password'];
    $database = getDatabase();
    $login = $database->get( "users",
        ["uid","nickname(username)","avatar","signature(sign)","groups_list"],
        ["username"=>$username,"password"=>$passwprd] );
    if( $login['uid'] != FALSE ){
        $result['status'] = '1';
        $result['url'] = 'chat.html';
        $login['sign'] = addslashes($login['sign']); // 转义防止反斜杠导致json格式不正确
        $result['user_data'] = $login;
        $result['tokenId'] = md5($login['uid'].time());
        // 生成Json Web Token作为后续验证token
        $key = "jay_z:wechat";
        $token = array(
            'iss' => 'www.wechat.com',  // 发行者
            'exp' => time()+3600,       // 过期时间
            'name' => $login['username'],
            'uid' => $login['uid']
        );
        $result['tokens'] = \JWT\JWT::encode($token,$key);
    }else{
        $result['status'] = '-1';
        $result['msg'] = '用户名不存在或密码有误!';
    }
    echo json_encode($result);
}

function decodeTokens(){
    try{
        $tokens = trim($_POST['tokens']);
        $key = "jay_z:wechat";
        $decoded = \JWT\JWT::decode($tokens, $key , array('HS256'));
        print_r($decoded);
    }catch(Exception $e){
        print_r($e->getMessage());
    }
    
}

// 保存用户个人签名
function savePersonalSign(){
    $uid = $_POST['uid'];
    $sign = $_POST['sign'];
    $database = getDatabase();
    $res = $database->update("users", ["signature"=>$sign] ,["uid"=>$uid]);
    echo json_encode(array('result'=>$res));
}

// 上传图片
function uploadImages(){
    require 'upload.php';
    $upload = new Upload();
    $result = $upload->uploadFile('file' , 'image');
    echo json_encode($result);
}

// 上传文件
function uploadFiles(){
    require 'upload.php';
    $upload = new Upload();
    $result = $upload->uploadFile('file', 'file');
    echo json_encode($result);
}

// 上传个人头像
function uploadAvatar($uid){
    require 'upload.php';
    $upload = new Upload();
    $result = $upload->uploadFile('avatar', 'avatar');
    $database = getDatabase();
    $database->update("users", ["avatar"=>$result['data']['src']],["uid"=>$uid]);
    echo json_encode($result);
}

// 检查cookie记录的用户是否在线,通过判断tokenId判断是否在同一浏览器上登录
function checkUserLogin(){
    $uid = intval($_POST['uid']);
    $res = check_uid_isonline($uid);
    $tokenId = '';
    if( $res != FALSE ){
        $redis = getRedis();
        $tokenId = $redis->hGet("users_tokenId_hash",$uid);
    }
    echo json_encode( array('result'=>$res,'tokenId'=>$tokenId) );
}

// 接收离线消息
function getOfflineMessage(){
    $uid = intval($_POST['uid']);
    $mongodb = getMongoDb();
    $result = array();
    // 拼接查询条件
    $where = array(
        '$or' => array(
            // 好友消息
            array(
                '$and' => array(
                    array('to_id' => $uid),
                    array('is_read' => 0)
                )
            ),
            // 群组消息
            array(
                'member_list' => $uid
            )
        )
    );
    $res = $mongodb->chat_content->find($where);
    foreach( $res as $data ){
        if( !empty($data) ){
            $user_info = get_user_info($data['from_uid']);
            // 获取mongodb数据集中ID(转换为字符串)
            $objIdArray = (array)$data['_id'];
            $objId = $objIdArray['$id'];
            $message = array(
                'username' => $user_info['username'],
                'avatar' => $user_info['avatar'],
                'id' => $data['type'] === 'friend' ? $data['from_uid'] : $data['to_id'],
                'type' => $data['type'],
                'content' => htmlspecialchars($data['content']),
                'timestamp' =>$data['timestamp'],
                'chatId' => $objId
            );
            array_push($result, $message);
        }   
    }
    echo json_encode($result);
}

// 获取历史聊天信息
function getHistoryChat(){
    $my_uid = intval($_POST['uid']);
    $page = intval($_POST['page']);
    $to_id = intval($_GET['id']);
    $type = trim($_GET['type']);
    $history = array();
    $mongodb = getMongoDb();
    // 拼接查询条件
    if( $type == 'friend' ){
        $query = array(
            '$or' => array(
                array(
                    '$and' => array(
                        array('type'=>$type),
                        array('from_uid'=>$my_uid),
                        array('to_id'=>$to_id)
                    )    
                ),
                array(
                    '$and' => array(
                        array('type'=>$type),
                        array('from_uid'=>$to_id),
                        array('to_id'=>$my_uid)
                    )
                )
            )
        );
    }else{
        $query = array(
            'type' => $type,
            'to_id' => $to_id
        );
    }
    // 获取分页数
    $count = $mongodb->chat_content->find($query)->count();
    $pages = intval($count / 10) + 1;
    // 获取查询的历史记录
    $datas = $mongodb->chat_content->find($query)->sort(array("timestamp"=>1))->limit(10)->skip(($page-1)*10);
    foreach( $datas as $data ){
        if( !empty($data) ){
            $from_info = get_user_info($data['from_uid']);
            $dataset = array(
                'username' => $from_info['username'],
                'id' => $data['from_uid'],
                'avatar' => $from_info['avatar'],
                'timestamp' => $data['timestamp'],
                'content' => $data['content']
            );
            array_push($history, $dataset);
        }
    }
    // 返回结果
    $return = array(
        'code' => '0',
        'msg' => '',
        'data' => $history,
        'pages' =>$pages
    );
    echo json_encode($return);
}

// 将群组与群员表的对应关系保存到redis的哈希表中
function saveGroupMemberList(){
    $database = getDatabase();
    $redis = getRedis();
    $list = $database->select("groups",["group_id","member_list"]);
    foreach( $list as $data ){
        $redis->hSet("group_member_list",$data['group_id'],$data['member_list']);
    }
}

// 获取用户详细信息
function userDetailInfo(){
    $database = getDatabase();
    $uid = intval($_POST['uid']);
    $detail_info = $database->get("user_info",'*',["uid"=>$uid]);
    if( !empty($detail_info) ){
        $extra_info = $database->get("users",["avatar","signature"],["uid"=>$uid]);
        $detail_info = array_merge($detail_info,$extra_info);
        if($detail_info['birthday']){
            $detail_info['birthday'] = date("Y-m-d",strtotime($detail_info['birthday']));
        }
    }else{
        // 若无记录则新增一条记录
        $database->insert("user_info",["uid"=>$uid]);
        $detail_info = $database->get("users",["avatar","signature"],["uid"=>$uid]);
    }
    echo json_encode($detail_info);
}

// 保存用户详细信息
function saveUserDetailInfo(){
    $database = getDatabase();
    // 接口权限认证
    decrypt(trim($_POST['token']));
    $result = array('code'=>'0');
    $user_info = $_POST;
    $res1 = $database->update("user_info",
        ["mobile"=>$user_info['phone'],"email"=>$user_info['email'],"education"=>$user_info['education'],
            "shengxiao"=>$user_info['shengxiao'],"sex"=>isset($user_info['sex']) ? $user_info['sex'] : '',"birthday"=>$user_info['birthday']],
        ["uid" => $user_info['uid']]);
    $res2 = $database->update("users",["signature"=>$user_info['signature']],["uid"=>$user_info['uid']]);
    echo json_encode($result);
}

// 获取好友分组列表
function userGroupList(){
    $database = getDatabase();
    $uid = intval($_POST['uid']);
    $user_group = $database->select("friend_tag",["tag_id","tag_name","non_deleted"],["belongs_uid"=>$uid]);
    echo json_encode($user_group);
}

// 移除好友分组
function removeUserGroup(){
    $database = getDatabase();
    $database->action(function($database){
        $result = array('code'=>'0');
        
        $tag_id = intval($_POST['tag_id']);
        $user_list = $database->get("friend_tag",["list","belongs_uid(uid)"],["tag_id"=>$tag_id]);
        $default_tag = $database->get("friend_tag",["tag_id","list"],["belongs_uid"=>$user_list['uid'],"non_deleted"=>'1']);
        // 好友合并
        $join_user_list = array_merge(json_decode($user_list['list'],true),json_decode($default_tag['list'],true));
        $res1 = $database->update("friend_tag",["list"=>json_encode($join_user_list)],["tag_id"=>$default_tag['tag_id']]);
        $res2 = $database->delete("friend_tag",["tag_id"=>$tag_id]);
        // 修改失败则回滚
        if( $res1 == FALSE || $res2 == FALSE ){
            $result['code'] = '-1';
            $result['msg'] = '网络异常,操作失败!';
            return FALSE;
        }
        
        echo json_encode($result); 
    });
}


