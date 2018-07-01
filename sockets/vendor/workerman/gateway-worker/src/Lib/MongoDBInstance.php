<?php
namespace GatewayWorker\Lib;
require dirname(__FILE__).'/../../../../../../interface/MongoDB/MongoDB.php';
class MongoDBInstance
{
    protected static function getMongoDb(){
        $mongodb = new \MongoDB\MongoDB();
        return $mongodb;
    }
    
    /**
     * 插入数据
     * @param array $insert_data
     * @return string
     */
    public static function saveChatMessage($insert_data){
        $mongodb = self::getMongoDb();
        $result = $mongodb->insert($insert_data,"chat_content");
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
        $where = [
            '_id' => new \MongoDB\BSON\ObjectId($id)
        ];
        $action = array(
            '$set' => $field
        );
        $result = $mongodb->update($where,$action,"chat_content",true);
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
        $where = [
            '_id' => new \MongoDB\BSON\ObjectId($id)
        ];
        $result = $mongodb->update($where,$condition,"chat_content",true);
        return json_encode($result);
    }
    
}
