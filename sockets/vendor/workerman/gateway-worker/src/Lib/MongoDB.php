<?php 

namespace GatewayWorker\Lib;

class MongoDB{
    
    protected static function getMongoDb(){
        $mongodb = new \MongoClient("mongodb://localhost:27017");
        $database = $mongodb->wechat;
        return $database;
    }
    
    /**
     * 插入数据
     * @param array $insert_data
     * @return string
     */
    public static function saveChatMessage($insert_data){
        $mongodb = self::getMongoDb();
        $result = $mongodb->chat_content->insert($insert_data);
        return json_encode($result);      
    }
    
    /**
     * 通过ID作为条件进行修改
     * @param String $id
     * @param array $field
     * @return string
     */
    public static function updateChatMessageById($id,$field){
        $mongodb = self::getMongoDb();
        $where = array(
            '_id' => new \MongoId($id)
        );
        $action = array(
            '$set' => $field
        );
        $result = $mongodb->chat_content->update($where,$action);
        return json_encode($result);
    }
    
    /**
     * 通过ID作为条件,传入自定义修改条件
     * @param String $id
     * @param array $condition
     * @return string
     */
    public static function updateChatMessageByCondition($id,$condition){
        $mongodb = self::getMongoDb();
        $where = array(
            '_id' => new \MongoId($id)
        );
        $result = $mongodb->chat_content->update($where,$condition);
        return json_encode($result);
    }
    
}