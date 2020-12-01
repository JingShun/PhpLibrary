<?php
/**
 * 擴充功能
 */
class ObjExp{
    
    /**
     * 取得指定屬性或元素，若找找不到就返回空字串
     * 2019/06/21 | 景舜
     */
    public static function Val($obj,$key){
        //if(!$obj || !$key) return '';

        if(is_array($obj) && array_key_exists($key,$obj))
            return $obj[$key];
        else if(is_object($obj) && property_exists($obj,$key)){
            $rp = new ReflectionProperty($obj,$key);
            return $rp->getValue($obj);
        }
        else if(is_string($obj) || is_numeric($obj))
            return $obj;
        else
            return '';
    }

}