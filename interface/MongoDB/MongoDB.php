<?php
namespace MongoDB;

class MongoDB{
    
    private $manager;
    
    private $bulk;
    
    private $writeConcern;
    
    private $databaseName = 'wechat';
    
    private $return;
    
    public function __construct(){
        $this->manager = new \MongoDB\Driver\Manager("mongodb://localhost:27017");
        //$this->manager = new \MongoDB\Driver\Manager("mongodb://10.68.17.110:20000,10.68.17.106:20000,10.68.17.109:20000");
        $this->bulk = new \MongoDB\Driver\BulkWrite;
        $this->writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY,1000);
        $this->return = array(
            'error' => 0,
            'msg'   => ''
        );
    }
    
    /**
     * 添加文档(仅添加一条数据)
     * @param array $data 添加的文档
     * @param String $collectionName 集合名称
     */
    public function insert($data,$collectionName){
        // 向bulk添加一个insert操作
        $_id = $this->bulk->insert($data);
        $namespace = $this->databaseName.".".$collectionName;
        try{
           // 执行bulk中的多个写操作
           $result = $this->manager->executeBulkWrite($namespace,$this->bulk,$this->writeConcern);
        }catch (\Exception $e){
           $this->return['error'] = 1;
           $this->return['msg'] = $e->getMessage();
           return $this->return;
        }
        $this->return['insertId'] = $_id->__toString();
        return $this->return;
    }
    
    public function insertMany($data,$collectionName){
        $ids = array();
        foreach($data as $document){
            $_id = $this->bulk->insert($document);
            array_push($ids, $_id->__toString());
        }
        $namespace = $this->databaseName.".".$collectionName;
        try {
            $result = $this->manager->executeBulkWrite($namespace,$this->bulk,$this->writeConcern);
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
        $this->return['insertId'] = $ids;
        return $this->return;
    }
    
    /**
     * 删除文档
     * @param array $filter 刷选条件
     * @param Boolean $limit 删除数据的条数(true-仅删除第一条匹配的,false-删除所有)
     * @param String $collectionName 集合名称
     * 
     * guides
     * $filter格式如：[ 'name'=>['='=>'jay'],'age'=>['>'=>25] ]
     */
    public function delete($filter,$collectionName,$limit=true){
        $filters = array();
        foreach ($filter as $k1=>$v1){
            foreach($v1 as $k2=>$v2){
                $filters = array_merge($filters,$this->getCondition($k2, $k1, $v2));
            }
        }
        $options = array(
            'limit' => $limit
        );
        // 向bulk添加一个delete操作
        $this->bulk->delete($filters,$options);
        $namespace = $this->databaseName.".".$collectionName;
        try{
            // 执行bulk中的多个写操作
            $result = $this->manager->executeBulkWrite($namespace,$this->bulk,$this->writeConcern);
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
        return $this->return;   
    }
    
    /**
     * 更新文档
     * @param array $filter 刷选条件
     * @param array $newObj 更新|替换的文档
     * @param String $collectionName 集合名称
     * @param boolean $replacement 是否是替换-true(替换)、false(更新)
     * @param String 更新操作符
     * @param boolean $multi 是否更新所有匹配的文档(当$replacement为true时$multi不能为true)
     * @param boolean $upsert 若filter没有匹配则是否将newObj添加到数据库($replacement为true)
     * 
     * guides
     * $filter格式如：[ 'name'=>['='=>'jay'],'age'=>['>'=>25] ]
     * $newObj格式如：
     * [
     *    '$inc' => [age => 1],
     *    '$push' => [school => ' 北大']
     * ]
     * $replacement可抛弃,当update文档中无更新操作符时，即可认为是替换操作
     * 
     */
    public function update($filter,$newObj,$collectionName,$multiCondition=false,$replacement=false/* ,$instruct="set" */,$multi=false,$upsert=false){
        if( !$multiCondition ){
            $filters = array();
            foreach ($filter as $k1=>$v1){
                foreach($v1 as $k2=>$v2){
                    $filters = array_merge($filters,$this->getCondition($k2, $k1, $v2));
                }
            }
        }else{
            $filters = $filter;
        }
        /* if(!$replacement){
            $newObj = array(
                '$'.$instruct => $newObj
            );
        } */
        $updateOptions = array(
            'multi' => $multi == true ? $replacement == true ? false : true : false,
            'upsert' => $upsert == true ? $replacement == true ? true : false : false
        );
        $this->bulk->update($filters,$newObj,$updateOptions);
        $namespace = $this->databaseName.".".$collectionName;
        try {
            $this->manager->executeBulkWrite($namespace,$this->bulk,$this->writeConcern);
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
        return $this->return;
    }
    
    /**
     * 查询操作
     * @param array $filter 刷选条件
     * @param array $sort 排序条件
     * @param array $field 查询获取字段
     * @param String $collectionName 集合名称
     * @param int $limit 分页的条数
     * @param int $page 当前的页码
     * @param boolean $hasId 是否获取_id
     * @param boolean $multiCondition 是否使用元素组合条件(不进行转换)
     * 
     * guides
     * $filter格式如：[ 'name'=>['='=>'jay'],'age'=>['>'=>25] ]
     * $sort格式如：      [ 'age' =>'desc' ]
     * $field格式如：   [ 'name' , 'age' ... ]
     * 
     * 当$multiCondition为true时$filter传入的格式为(可按照旧版mongo驱动写filter条件)：
     * [ '$or' => [ [ $key1 => $value1 ] , [ $key2 => $value2 ] ] ]
     * 
     */
    public function query($filter,$sort,$field,$collectionName,$limit=10,$page=1,$hasId=true,$multiCondition=false){
        if( !$multiCondition ){
            $filters = array();
            foreach ($filter as $k1=>$v1){
                foreach($v1 as $k2=>$v2){
                    $filters = array_merge($filters,$this->getCondition($k2, $k1, $v2));
                }
            }
        }else{
            $filters = $filter;
        }
        foreach ((array)$sort as $k=>$v){
            $sort[$k] = strtolower($v) == 'asc' ? 1 : -1;
        }
        $fields = array();
        foreach ((array)$field as $key=>$value){
            $fields = array_merge($fields,array($value=>1));
        }
        $hasId != true && !empty($fields) ? $fields['_id'] = 0 : "";
//         $options = array(
//             'projection' => $fields,
//             'sort'       => $sort,
//             'limit'      => $limit,
//             'skip'       => ($page-1)*10
//         );
        $options = array();
        !empty($fields) ? $options['projection'] = $fields : "";
        !empty($sort) ? $options['sort'] = $sort : "";
        $limit==0 ? "" : $options['limit'] = $limit;
        $page==0 ? "" : $options['skip'] = ($page-1)*10;
        try{
            $query = new \MongoDB\Driver\Query($filters,$options);
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
        $namespace = $this->databaseName.".".$collectionName;
        try{
            $cursor = $this->manager->executeQuery($namespace,$query);
            $this->return['result'] = $cursor->toArray();
            return $this->return;
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
    }
    
    /**
     * 获取数据条数
     * @param array $filter 刷选条件
     * @param String $collectionName 集合名称
     * @param boolean $multiCondition 是否使用元素组合条件(不进行转换)
     * 
     * guides
     * $filter格式如：[ 'name'=>['='=>'jay'],'age'=>['>'=>25] ]
     */
    public function count($filter,$collectionName,$multiCondition=false){
        if(!$multiCondition){
            $filters = array();
            foreach ($filter as $k1=>$v1){
                foreach($v1 as $k2=>$v2){
                    $filters = array_merge($filters,$this->getCondition($k2, $k1, $v2));
                }
            }
        }else{
            $filters = $filter;
        }
        $document = [
            'count' => $collectionName,
            'query' => $filters
        ];
        $return = $this->executeCommand($document);
        if($return['error'] == 0){
            $return['response'] = $return['response']->n;
            return $return;
        }else{
            return $return;
        }
    }
    
    public function aggregate($collectionName,$pipeline){
        $document = [
            'aggregate' => $collectionName,
            'pipeline' => $pipeline
        ];
        $return = $this->executeCommand($document);
        if($return['error'] == 0){
            $return['response'] = $return['response']->result;
            return $return;
        }else{
            return $return;
        }
    }
    
    public function executeCommand($document){
        try{
            $command = new \MongoDB\Driver\Command($document);
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
        try{
            $cursor = $this->manager->executeCommand($this->databaseName,$command);
            $this->return['response'] = $cursor->toArray()[0];
            return $this->return;
        }catch (\Exception $e){
            $this->return['error'] = 1;
            $this->return['msg'] = $e->getMessage();
            return $this->return;
        }
    }
    
    private function getCondition($condition,$key,$value){
        $array = array();
        switch ($condition){
            case '=':
                $array[$key]=$value;
                break;
            case '>':
                $array[$key] = ['$gt' => $value];
                break;
            case '<':
                $array[$key] = ['$lt' => $value];
                break;
            case '>=':
                $array[$key] = ['$gte' => $value];
                break;
            case '<=':
                $array[$key] = ['$lte' => $value];
                break;
            case '!=':
                $array[$key] = ['$ne' => $value];
                break;
            default:
                $array[$key]=$value;
        }
        return $array;
    }
    
}
