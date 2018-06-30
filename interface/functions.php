<?php

/**
 * @desc 检查好友的在线状态
 * @param array $users  
 * @param int $mine_id
 * @return array
 */
function check_user_status($users,$mine_id){
    $redis = getRedis();
    // 获取在线用户的id数组
    $online_users = $redis->SMEMBERS("users_online_set");
    foreach($users as $key=>$value){
        if( in_array($value['id'],$online_users) || $value['id'] == $mine_id ){
            $users[$key]['status'] = 'online';
            $value['id'] == $mine_id ? $users[$key]['sorts'] = 0 : $users[$key]['sorts'] = 1;
        }else{
            $users[$key]['status'] = 'offline';
            $users[$key]['sorts'] = 2;
        }
    }
    return arraySort($users,'sorts','asc');
}

/**
 * 检查该用户是否已经处于在线状态
 * @param int $uid
 * @return boolean
 */
function check_uid_isonline($uid){
    $redis = getRedis();
    $online_users = $redis->SMEMBERS("users_online_set");
    return in_array($uid, $online_users) ? true : false; 
}

/**
 * @desc arraySort 二维数组排序 按照指定的key 对数组进行排序
 * @param array $arr 将要排序的数组
 * @param string $keys 指定排序的key
 * @param string $type 排序类型 asc | desc
 * @return array
 */
function arraySort($arr, $keys, $type = 'asc') {
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v){
        $keysvalue[$k] = $v[$keys];
    }
    $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
    reset($keysvalue);
    $i = 0;
    foreach ($keysvalue as $k => $v) {
        $new_array[$i] = $arr[$k];
        $i++;
    }
    return $new_array;
}

/**
 * 往json数据中添加值
 * @param string $json
 * @param mixed $value
 * @return string
 */
function push_data_to_json($json,$value){
    $array = json_decode($json,true);
    $array = !empty($array) ? $array : array();
    if( !in_array($value,$array) ){
        array_push($array, $value);
    }
    return json_encode($array);
}

/**
 * 检查对方是否为好友
 * @param int $my_uid
 * @param int $to_uid
 * @return boolean
 */
function check_is_friend($my_uid,$to_uid){
    $database = getDatabase();
    $groups = $database->select("friend_tag", "list" , [ "belongs_uid"=>$my_uid ] );
    $friend_uids = array();
    foreach ( $groups as $value ){
        $array = json_decode($value,true);
        $friend_uids = array_merge($friend_uids,$array);
    }
    return in_array($to_uid,$friend_uids);
}

/**
 * 检查是否已加入群组中
 * @param int $uid
 * @param string $member_list
 * @return boolean
 */
function check_is_in_group($uid,$member_list){
    $member_list = json_decode($member_list,true);
    return in_array($uid, $member_list);
}

/**
 * 获取用户的个人信息
 * @param int $uid
 * @return array
 */
function get_user_info($uid){
    $database = getDatabase();
    $info = $database->get("users",["nickname(username)","avatar"],["uid"=>$uid]);
    return $info;
}

/**
 * 获取群组的信息
 * @param int $group_id
 * @return array
 */
function get_group_info($group_id){
    $database = getDatabase();
    $info = $database->get("groups",["group_name(username)","group_avatar(avatar)"],["group_id"=>$group_id]);
    return $info;
}

/**
 * 获取mongodb资源
 * @return Mongodb
 */
function getMongoDb(){
    $mongodb = new MongoClient("mongodb://localhost:27017");
    $database = $mongodb->wechat;
    return $database;
}

/**
 * 获取redis资源
 * @return Redis
 */
function getRedis(){
    $redis = new Redis();
    $redis->connect("127.0.0.1:6379");
    return $redis;
}

// 获取数据库源
function getDatabase(){
    $config = require 'config_inc.php';
    $database = new \Medoo\medoo([
        'database_type' => 'mysql',
        'database_name' => $config['mysql_database'],
        'server' => $config['mysql_host'],
        'username' => $config['mysql_username'],
        'password' => $config['mysql_password'],
        'charset' => 'utf8',
        'port' => 3306
    ]);
    return $database;
}

// RSA私钥解密
function decrypt($encrypted){
    //RSA私钥
    $private_key='-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDAbfx4VggVVpcfCjzQ+nEiJ2DLnRg3e2QdDf/m/qMvtqXi4xhw
vbpHfaX46CzQznU8l9NJtF28pTSZSKnE/791MJfVnucVcJcxRAEcpPprb8X3hfdx
KEEYjOPAuVseewmO5cM+x7zi9FWbZ89uOp5sxjMnlVjDaIczKTRx+7vn2wIDAQAB
AoGAZzUWajxKTZeJqh5FjBgmwZi5M7voFynZAjRWAkCkqZye0FfY7e70kA92C1AL
aVqySnNr4WYZuGorEeOFGqHIv1XSowTLgfLkVBZ/SXiep2QYJrR0YevjysvLnTfb
mrdWCqWSj+0AlQg+AvDA/qtvBVMxKymbpo+4bj5H2pPPZ1ECQQDi1PwJQJBYPbpL
vGmP3AmWg467tCeQ+aJGgtQTOK5BH+p0BWFVDX583R437vllkKI8EXgZfqQfsQcj
7XUAXyZVAkEA2SyFbO8roH9JLrEoxxKGeiGZvhPfNl9nXLhX0OFS0ywQaVBJno39
9W5bX5iP5Jzeb3UWsZ/TxzhGc/b4WjAlbwJBAOFuIn1feRT5Y+hY++BJIg4/+N57
EMd4ENpas0HXFvcKLQvZPP42Rvr5FksoaRuTPmjMQ7uyrJICccI3AAy6g3ECQQDE
AyH9+zRmLNxRj0advsOvUcpgu7DYc21oS12/Qs+tl3TMiNGZkNDphwxjkOA217sP
4B92fCn6AnncSslHJXNzAkBo6ujxqIfrZMOG3ON9nXxkWlq39GFS6CzXWscHA3Xz
FMVT1WWU3FR2Kf2QSKiMGv02YcI2xfowim3JnT6600N0
-----END RSA PRIVATE KEY-----';

    // 验证失败返回
    $return = array(
        'code' => '-1',
        'msg'  => '你没有权限进行操作'
    );
    
    //私钥解密
    openssl_private_decrypt(base64_decode($encrypted),$decrypted,$private_key);
    if(!empty($decrypted)) {
        $array = json_decode($decrypted, true);
        if( !isset($array['action']) ){
            echo json_encode($return);exit;
        }
        if($array['action'] == 'userLogin'){
            return $array;
        }else{
            if( !isset($array['uid']) || !isset($array['token']) ){
                echo json_encode($return);exit;
            }
            $redis = getRedis();
            $check_token = $redis->hGet("users_tokenId_hash",$array['uid']);
            if( $check_token != $array['token'] ){
                echo json_encode($return);exit;
            }
        }
    }else{
        echo json_encode($return);exit;
    }

}

function make_jwt_json(){
    
}

