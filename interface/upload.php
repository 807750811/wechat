<?php
/**
 * @desc 文件上传类
 * @return array
 *   code 0:表示成功;其他表示失败
 *   msg:失败原因
 *   data
 *      src:图片/文件路径url
 *      name:文件名称
 */
class Upload
{
    private $imageAllowExt = array('gif','jpg','jpeg','bmp','png','swf'); // 允许上传图片格式
    private $fileAllowExt = array('zip','pdf','xls','txt','doc','rar'); // 允许上传文件格式
    private $imageMaxSize = 1; // 限制最大图片上传1M
    private $fileMaxSize = 5; // 限制最大文件上传5M

    /**
     * 获取文件的信息
     * @param str $flag 上传文件的标识
     * @return arr 上传文件的信息数组
     */
    public function getInfo($flag)
    {
        return $_FILES[$flag];
    }

    /**
     * 获取文件的扩展名
     * @param str $filename 文件名
     * @return str 文件扩展名
     */
    public function getExt($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * 检测文件扩展名是否合法
     * @param str $filename 文件名
     * @return bool 文件扩展名是否合法
     */
    private function checkExt($filename,$type)
    {
        $ext = $this->getExt($filename);
        if($type == 'image' || $type == 'avatar'){
            return in_array($ext, $this->imageAllowExt);
        }else if($type == 'file'){
            return in_array($ext, $this->fileAllowExt);
        }
    }

    /**
     * 检测文件大小是否超过限制
     * @param int size 文件大小
     * @return bool 文件大小是否超过限制
     */
    public function checkSize($size,$type)
    {
        if($type == 'image' || $type == 'avatar'){
            return $size < $this->imageMaxSize*1024*1024;
        }else if($type == 'file'){
            return $size < $this->fileMaxSize*1024*1024;
        }
    }

    /**
     * 随机的文件名
     * @param int $len 随机文件名的长度
     * @return str 随机字符串
     */
    public function randName($len = 6)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJLMKOPQRSTUVWXYZ0123456789'),0,$len);
    }

    /**
     * 创建文件上传到的路径
     * @return str 文件上传的路径
     */
    public function createDir($type)
    {
        if($type == 'image'){
            $dir[0] = '../Uploads/chat_images/'.date('Y/m/d',time());
            $dir[1] = '/Uploads/chat_images/'.date('Y/m/d',time());
        }else if($type == 'file'){
            $dir[0] = '../Uploads/files/'.date('Y/m/d',time());
            $dir[1] = '/Uploads/files/'.date('Y/m/d',time());
        }else if($type == 'avatar'){
            $dir[0] = '../Uploads/avatar/'.date('Y/m/d',time());
            $dir[1] = '/Uploads/avatar/'.date('Y/m/d',time());
        }
        if (is_dir($dir[0]) || mkdir($dir[0], 0777, true))
        {
            return $dir;
        }
    }

    /**
     * 文件上传
     * @param str $flag 文件上传标识
     * @param str $type image or file
     * @return arr 文件上传信息
     */
    public function uploadFile($flag,$type)
    {
        if ($_FILES[$flag]['name'] === '' || $_FILES[$flag]['error'] !== 0)
        {
            $result = array(
                'code' => '-1',
                'msg' => '没有上传文件'
            );
            return $result;
        }
        $info = $this->getInfo($flag);
        if (!$this->checkExt($info['name'],$type))
        {
            $result = array(
                'code' => '-1',
                'msg' => '不支持的文件类型'
            );
            return $result;
        }
        if (!$this->checkSize($info['size'],$type))
        {
            $result = array(
                'code' => '-1',
                'msg' => '文件大小超过限制'
            );
            return $result;
        }
        $filename = $this->randName().'.'.$this->getExt($info['name']);
        $dir = $this->createDir($type);
        if (!move_uploaded_file($info['tmp_name'], $dir[0].'/'.$filename))
        {
            $result = array(
                'code' => '-1',
                'msg' => '文件上传失败'
            );
            return $result;
        }
        else
        {
            if($type == 'image' || $type == 'avatar'){
                $data = array('src' => $dir[1].'/'.$filename);
            }else if($type == 'file'){
                $data = array('src' => $dir[1].'/'.$filename , 'name' => $filename);
            }
            $result = array(
                'code' => '0',
                'msg' => '',
                'data' => $data
            );
            return $result;
        }
    }
}