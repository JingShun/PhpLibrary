<?php

/**
 * 陣列相關的擴充功能
 * 
 * @version 0.2.1 修正columns遇上物件陣列會出錯
 * @version 0.2.0 加入except_columns
 * @version 0.1.2 讓group_by的不訂參數可以吃單一陣列
 * @version 0.1.1 修正call_user_func_array在static下的調整
 * @version 0.1.0 加入一些功能，sort_by可以考慮怎麼加入倒序控制
 */
class ArrExp
{

    // public static function equal(array $arr1, array $arr2){

    //     if(gettype($arr1) != gettype($arr2)) return false;
    //     if(count($arr1) != count($arr2)) return false;


    // }

    /** 排除重複，可用於二維陣列與物件陣列 */
    public static function unique(array $arr)
    {
        $firstItem = current($arr);
        if (!is_array($firstItem) && !is_object($firstItem)) {
            return array_keys(array_flip($arr));
        } else {
            return array_unique($arr, SORT_REGULAR);
        }
    }

    /**
     * 返回数组中指定多列
     * @see https://blog.csdn.net/fdipzone/article/details/78071676
     *
     * @param  Array  $input       需要取出數組列的多維數組
     * @param  String|Array $column_keys 要取出的列名，傳入陣列或逗號分隔，如不傳則返回所有列
     * @param  String $index_key   作為返回數組的索引的列
     * @return Array
     */
    public static function columns(array $input, $column_keys = null, $index_key = null)
    {
        if (!is_array($input)) return [];
        $result = array();

        $keys = isset($column_keys) ? (is_array($column_keys) ? $column_keys : explode(',', $column_keys)) : array();
        foreach ($input as $k => $v) {
            // 指定返回列
            if ($keys) {
                $tmp = array();
                foreach ($keys as $key) {
                    $tmp[$key] = is_object($v) ? $v->$key : $v[$key];
                }
                if (is_object($v)) $tmp = (object)$tmp;
            } else {
                $tmp = $v;
            }
            // 指定索引列
            if (isset($index_key)) {
                $result[$v[$index_key]] = $tmp;
            } else {
                $result[] = $tmp;
            }
        }
        return $result;
    }

    /**
     * 移除数组中指定多列
     * @see https://blog.csdn.net/fdipzone/article/details/78071676
     *
     * @param  Array  $input       需要取出數組列的多維數組
     * @param  String|Array $column_keys 要移除的列名，傳入陣列或逗號分隔，如不傳則返回所有列
     * @return Array
     */
    public static function except_columns(&$array, $keys)
    {
        if(!isset($keys)) return $array;

        if (!is_array($keys))
            $key = explode(',', $keys);

        $tmp = array_flip($keys);
        $tmp2 = array_diff_key($array, $tmp);

        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Groups an array by a given key. Any additional keys will be used for grouping the next set of sub-arrays.
     * <p>example :  $grouped = array_group_by($records, 'state', 'city');</p>
     * 
     * @author Jake Zatecky (https://github.com/jakezatecky/array_group_by/tree/v1.1.0)
     *
     * @param array $arr     The array to be grouped.
     * @param mixed $key,... A set of keys to group by.
     *
     * @return array
     */
    public static function group_by(array $arr, $key)
    {
        if (!is_array($arr)) {
            trigger_error('group_by(): The first argument should be an array', E_USER_ERROR);
        }
        if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key)) {
            trigger_error('group_by(): The key should be a string, an integer, a float, or a function', E_USER_ERROR);
        }
        // 陣列自動轉成不定參數
        if (is_array($key)) {
            return call_user_func_array('self::group_by', $key);
        }

        $isFunction = !is_string($key) && is_callable($key);

        // Load the new array, splitting by the target key
        $grouped = [];
        foreach ($arr as $value) {
            $groupKey = null;

            if ($isFunction) {
                $groupKey = $key($value);
            } else if (is_object($value)) {
                $groupKey = $value->{$key};
            } else {
                $groupKey = isset($value[$key]) ? $value[$key] : '';
            }

            $grouped[$groupKey][] = $value;
        }

        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();

            foreach ($grouped as $groupKey => $value) {
                $params = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$groupKey] = call_user_func_array('self::group_by', $params);
            }
        }

        return $grouped;
    }

    // public static function group_by2(array $arr,array $keys)
    // {
    //     $result = [];
    //     $keys = self::unique(array_filter($arr, function($v){return (isset($v) && trim($v) !== '');}));
    //     foreach ($arr as $row ) {
    //         $result[$row[$keys[0]]] = $row;
    //     }
    //     return $result;
    // }


    public static function sort_by(array $arr, $keys)
    {

        // 整理要排序的屬性
        if (is_object($keys)) return $arr;
        if (!is_array($keys))
            $keyList = explode(',', $keys); // 分割
        $keyList = array_keys(array_flip($keyList)); // 排除重複
        $keyList = array_reverse($keyList); // 從最後面開始排序，所以要反轉

        $result = &$arr; // 節省一次占用空間
        $first = current($arr); // 拿來辨識物件還是陣列用，因為屬性存取的方式不一樣

        // 開始排序
        if (is_object($first))
            foreach ($keyList as $key) {
                if (isset($first->{$key}))
                    usort($result, function ($a, $b) use ($key) {
                        return strcmp($a->{$key}, $b->{$key});
                    });
            }
        else if (is_array($first))
            foreach ($keyList as $key) {
                if (array_key_exists($key, $first))
                    usort($result, function ($a, $b) use ($key) {
                        return strcmp($a[$key], $b[$key]);
                    });
            }

        return $result;
    }
}
