<?php
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\MongoDB;

require_once '/usr/local/apache2/htdocs/wechat/sockets/vendor/workerman/mysql/src/Connection.php';
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
   // 保存数据库实例
   public static $db = null;
   
   // 进程启动后初始化数据库连接
   public static function onWorkerStart($worker){
       self::$db = new Workerman\MySQL\Connection("localhost", "3306", "root", "zxj1105511101", "wechat");
   }
   
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $data) {
       // 建立redis连接
       $redis = new Redis();
       $redis->connect("127.0.0.1:6379");
       // 解析json数据
       $message = json_decode($data, true);
       $message_type = $message['type'];
       switch($message_type) {
           // 建立连接操作
           case 'init':
               $uid = $message['uid'];
               // 判断当前用户是否已登录,剔除已登录端
               if( Gateway::isUidOnline($uid) != FALSE ){
                   $old_clientId = Gateway::getClientIdByUid($uid);
                   $tokenId = ( $redis->hExists("users_tokenId_hash",$uid) != FALSE && $redis->hExists("users_tokenId_hash",$uid)!= $message['tokenId'] )
                    ? $redis->hGet("users_tokenId_hash",$uid) : "";
                   $other_login_message = array(
                       'message_type' => 'other_login',
                       'tokenId'      => $tokenId
                   );
                   Gateway::unbindUid($old_clientId[0], $uid);
                   Gateway::sendToClient($old_clientId[0], json_encode($other_login_message));
               }
               // 判断当前浏览器是否已经有其他登录的用户,将前者登录的用户逼退
               if( $message['login_status'] != FALSE && Gateway::isUidOnline($message['login_status']) != FALSE ){
                   $old_clientId = Gateway::getClientIdByUid( $message['login_status'] );
                   $tokenId = $redis->hExists("users_tokenId_hash",$message['login_status']) != FALSE ? $redis->hGet("users_tokenId_hash",$message['login_status']) : "";
                   $other_login_message = array(
                       'message_type' => 'other_login',
                       'tokenId'      => $tokenId
                   );
                   Gateway::unbindUid($old_clientId[0], $message['login_status']);
                   Gateway::sendToClient($old_clientId[0], json_encode($other_login_message));
               }
               
               // 设置session保存用户数据
               $_SESSION = array(
                   'username' => $message['username'],
                   'avatar'   => $message['avatar'],
                   'id'       => $uid,
                   'sign'     => $message['sign']
               );
               // 将当前链接与uid绑定
               Gateway::bindUid($client_id, $uid);
               // 绑定当前用户与所有群组的关联关系
               $groups_list = $message['groups_list'];
               if($groups_list!=false && $groups_list!='undefined'){
                   $groups_list = json_decode($groups_list,true);
                   foreach((array)$groups_list as $value){
                       Gateway::joinGroup($client_id, $value);
                   }
               }
               // 将当前用户加入到在线用户集合(记录在线用户)
               $redis->sAdd("users_online_set",$uid);
               
               // 记录正在登录的客户端的tokenId
               if( $redis->hExists("users_tokenId_hash",$uid) != FALSE ){
                   $redis->hSet("users_tokenId_hash",$uid,$message['tokenId']);
               }else{
                   $redis->hSetNx("users_tokenId_hash",$uid,$message['tokenId']);
               }
               
               // 通知所有用户该用户上线
               $status_message = array(
                   'message_type' => 'online',
                   'id' => $_SESSION['id']
               );
               Gateway::sendToAll(json_encode($status_message));
               return;
           // 发送聊天消息
           case 'chatMessage':
               $type = $message['data']['to']['type'];
               // 接收方ID( 用户ID或群组ID )
               $to_id = $message['data']['to']['id'];
               // 发送端ID
               $uid = $_SESSION['id'];
               
               // Mongodb保存聊天记录
               $save_data = array(
                   'type' => $type,
                   'from_uid' => intval($uid),
                   'to_id' => intval($to_id),
                   'content' => htmlspecialchars($message['data']['mine']['content']),
                   'timestamp' => time()*1000
               );
               if($type === 'friend'){
                   $save_data['is_read'] = 0;
               }else{
                   $member_list = $redis->hGet("group_member_list",$to_id);
                   // 发送人不保存到未发送人列表中
                   $member_list = array_merge( array_diff( json_decode($member_list,true),array($uid) ) );
                   $save_data['member_list'] = $member_list;
               }
               MongoDB::saveChatMessage($save_data);
               // 获取插入mongodb的ID
               $objIdArray = (array)$save_data["_id"];
               $insertId = $objIdArray['$id'];
               
               $chat_message = array(
                    'message_type' => 'chatMessage',
                    'data' => array(
                        'username' => $_SESSION['username'],
                        'avatar'   => $_SESSION['avatar'],
                        'id'       => $type === 'friend' ? $uid : $to_id,
                        'type'     => $type,
                        'content'  => htmlspecialchars($message['data']['mine']['content']),
                        'timestamp'=> time()*1000,
                        'chatId'   => $insertId
                    )
               );
               
               switch ($type) {
                   // 私聊
                   case 'friend':
                       return Gateway::sendToUid($to_id, json_encode($chat_message));
                   // 群聊
                   case 'group':
                       return Gateway::sendToGroup($to_id, json_encode($chat_message), $client_id);
               }
               return;
           // 设置在线或隐身状态
           case 'hide':
           case 'online':
               $status_message = array(
                   'message_type' => $message_type,
                   'id'           => $_SESSION['id'],
               );
               $_SESSION['online'] = $message_type;
               Gateway::sendToAll(json_encode($status_message));
               return;
           // 设置聊天状态(是否为正在输入状态)
           case 'chatStatus':
               $status_message = array(
                    'message_type' => $message_type,
                    'id'           => $_SESSION['id'],
                    'status'       => $message['status']
               );
               return Gateway::sendToUid($message['data']['to']['id'], json_encode($status_message));
           // 消息提示推送
           case 'msg_notice':
               $notice_message = array(
                    'message_type' => $message_type
               );
               return Gateway::sendToUid($message['to_uid'], json_encode($notice_message));
           // 推送添加好友成功
           case 'friend_add_success':
               $send_message = array(
                    'message_type' => $message_type,
                    'user_data'    => $message
               );
               return Gateway::sendToUid($message['to_uid'], json_encode($send_message));
           // 推送添加群组成功
           case 'group_add_success':
               $send_message = array(
                    'message_type' => $message_type,
                    'group_data'   => $message 
               );
               return Gateway::sendToUid($message['to_uid'], json_encode($send_message));
           // 推送群组添加新用户系统消息
           case 'group_system_message':
               $join_client = Gateway::getClientIdByUid($message['from_uid']);
               Gateway::joinGroup($join_client[0], $message['group_id']);
               $send_message = array(
                    'message_type' => $message_type,
                    'message_data' => $message
               );
               return Gateway::sendToGroup($message['group_id'], json_encode($send_message), $client_id);
           // 用户接收到消息做出回复表示已接收
           case 'answer_is_read':
               $from_type = $message['from_type'];
               if( $from_type == 'friend' ){
                   // 设置该消息为已读消息表示已接收
                   $update_field = array('is_read' => 1);
                   MongoDB::updateChatMessageById($message['chat_id'], $update_field);
               }else if( $from_type == 'group' ){
                   $received_uid = intval($message['received_uid']);
                   $update_field = array(
                       '$pull' => array(
                            'member_list' =>  $received_uid
                       )
                   );
                   MongoDB::updateChatMessageByCondition($message['chat_id'], $update_field);
               }
               return;
           /*--------------------------------------------------------------------------------------*/
           // 游戏邀请
           case 'game_invitation':
               $from_uid = $message['from_uid'];
               $to_uid = $message['to_uid'];
               $all_onGame_uid = $redis->hKeys("users_gameId_hash");
               if( Gateway::isUidOnline($to_uid)!=FALSE 
                   && !in_array($to_uid, $all_onGame_uid) 
                   && !in_array($from_uid, $all_onGame_uid) ){
                   $send_message = array(
                       'message_type' => $message_type,
                       'from_uid'     => $from_uid
                   );
                   return Gateway::sendToUid($to_uid,json_encode($send_message));
               }else if( in_array($to_uid, $all_onGame_uid) ){
                   $send_message = array(
                       'message_type' => 'competitor_is_onGame',
                   );
                   return Gateway::sendToUid($from_uid, json_encode($send_message));
               }else if( in_array($from_uid, $all_onGame_uid) ){
                   $send_message = array(
                       'message_type' => 'inviter_is_onGame',
                   );
                   return Gateway::sendToUid($from_uid, json_encode($send_message));
               }else{
                   $send_message = array(
                       'message_type' => 'game_invitation_fail',
                   );
                   return Gateway::sendToUid($from_uid, json_encode($send_message));
               }
           // 取消游戏邀请
           case 'game_invitation_cancel':
               $to_uid = $message['to_uid'];
               $send_message = array(
                   'message_type' => $message_type
               );
               return Gateway::sendToUid($to_uid, json_encode($send_message));    
           // 拒绝对方的游戏邀请
           case 'game_invitation_refuse':
               $from_uid = $message['from_uid'];
               $send_message = array(
                   'message_type' => $message_type
               );
               return Gateway::sendToUid($from_uid, json_encode($send_message));
           // 接受游戏的邀请
           case 'game_invitation_receive':
               $from_uid = $message['from_uid'];
               $to_uid = $message['to_uid'];
               $send_message = array(
                   'message_type' => $message_type
               );
               $send_message['competitor'] = $from_uid;
               $send_message['playerRole'] = 2;
               Gateway::sendToUid($to_uid, json_encode($send_message));
               $send_message['competitor'] = $to_uid;
               $send_message['playerRole'] = 1;
               Gateway::sendToUid($from_uid, json_encode($send_message));
               return;
           // 游戏初始化
           case 'game_init':
               $uid = $message['uid'];
               $competitor = $message['competitor'];
               if($redis->hExists("users_gameId_hash",$uid)==FALSE){
                   $redis->hSet("users_gameId_hash",$uid,$client_id);
                   $_SESSION['game_uid'] = $uid;
                   $_SESSION['competitor_uid'] = $competitor;
               }
               // 记录当前用户的游戏状态已准备好
               if( $redis->sIsMember('users_game_isReady_set',$uid)==FALSE ){
                   $redis->sAdd('users_game_isReady_set',$uid);
               }
               // 检查用户是否存在游戏记录，若无则插入新数据
               $check_result = self::$db->select('rec_id')->from('game_result')->where("uid = {$uid}")->single();
               if(empty($check_result)){
                   self::$db->insert('game_result')->cols(array(
                       'uid' => $uid
                   ))->query();
               }
               return;
           // 传输下棋的操作
           case 'play_chess':
               $competitor = $message['competitor_uid'];
               $x_site = $message['x'];
               $y_site = $message['y'];
               $send_message = array(
                   'message_type' => $message_type,
                   'x_site'       => $x_site,
                   'y_site'       => $y_site
               );
               $competitor_clientId = $redis->hGet("users_gameId_hash",$competitor);
               Gateway::sendToClient($competitor_clientId, json_encode($send_message));
               return;
           // 游戏结束，重置用户游戏状态
           case 'game_is_finish':
               $redis->srem('users_game_isReady_set',$_SESSION['game_uid']);
               // 根据游戏结束返回结果更新数据库
               $game_result = $message['isWin'];
               if($game_result == 1){
                   self::$db->query(" UPDATE `game_result` SET `win_times` = `win_times`+1 WHERE uid = '".$_SESSION['game_uid']."' ");
               }else if($game_result == 0){
                   self::$db->query(" UPDATE `game_result` SET `lose_times` = `lose_times`+1 WHERE uid = '".$_SESSION['game_uid']."' ");
               }
               return;
           // 检查两端用户是否都已准备好
           case 'check_user_isReady':
               $redis->sAdd('users_game_isReady_set',$_SESSION['game_uid']);
               if( $redis->sIsMember('users_game_isReady_set',$_SESSION['competitor_uid'])==TRUE ){
                   $from_clientId = $redis->hGet('users_gameId_hash',$_SESSION['game_uid']);
                   $to_clientId = $redis->hGet('users_gameId_hash',$_SESSION['competitor_uid']);
                   $send_message = array(
                       'message_type' => 'game_isReady'
                   );
                   Gateway::sendToAll(json_encode($send_message),array($from_clientId,$to_clientId));
               }
               return;
           /*--------------------------------------------------------------------------------------*/
           case 'ping':
               return;
           default:
               echo "unknown message $data";
       }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id) {
       // 建立redis连接
       $redis = new Redis();
       $redis->connect("127.0.0.1:6379");
       if( isset($_SESSION['id']) ){
           // 用户离线将此用户id从在线集合中移除( 判断uid是否在线来区分是离线还是多端登录逼退  )
           if( Gateway::isUidOnline($_SESSION['id'])==FALSE ){
                $redis->srem("users_online_set",$_SESSION['id']);
                $redis->hDel("users_tokenId_hash",$_SESSION['id']);
                // 通知所有用户该用户下线
                $logout_message = array(
                    'message_type' => 'logout',
                    'id'           => $_SESSION['id']
                );
                Gateway::sendToAll(json_encode($logout_message));
           }
       }
       // 游戏连接断开的处理
       if( isset($_SESSION['game_uid']) ){
           // 两者都处于就绪状态，先退出方则被认为是逃跑方
           if( $redis->sismember("users_game_isReady_set",$_SESSION['game_uid']) == TRUE &&
               $redis->sismember("users_game_isReady_set",$_SESSION['competitor_uid']) == TRUE ){
               self::$db->query(" UPDATE `game_result` SET `escape_times` = `escape_times` + 1 WHERE uid = '".$_SESSION['game_uid']."' ");
           }
           $redis->hDel("users_gameId_hash",$_SESSION['game_uid']);
           $redis->srem("users_game_isReady_set",$_SESSION['game_uid']);
           // 通知对方游戏下线
           $game_logout = array(
               'message_type' => 'game_logout'
           );
           if( $redis->hExists("users_gameId_hash",$_SESSION['competitor_uid'])!=FALSE ){
                Gateway::sendToUid($_SESSION['competitor_uid'], json_encode($game_logout));
           }
       }
   }
   
}
