<?php

/**
 * 陣列相關的擴充功能
 * 
 * @version 0.3.1 重寫group_by多key方法
 * @version 0.3.0 加入find,reset_key,transpose功能
 * @version 0.2.2 修正sort_by無法排序多個欄位的問題
 * @version 0.2.1 修正columns遇上物件陣列會出錯
 * @version 0.2.0 加入except_columns
 * @version 0.1.2 讓group_by的不訂參數可以吃單一陣列
 * @version 0.1.1 修正call_user_func_array在static下的調整
 * @version 0.1.0 加入一些功能，sort_by可以考慮怎麼加入倒序控制
 */
class ArrExp
{
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
     * 返回数组中指定多個欄位
     * @see https://blog.csdn.net/fdipzone/article/details/78071676
     *
     * @param  Array  $input       需要取出數組列的多維數組
     * @param  String|Array $column_keys 要取出的列名，傳入陣列或逗號分隔，如不傳則返回所有列
     * @param  String $index_key   作為返回數組的索引的列
     * @return Array
     */
    public static function columns(array $input, $column_keys = null, $index_key = null)
    {
        if (!is_array($input) || is_null($column_keys)) return $input;
        $result = array();

        $keys =  (is_array($column_keys) ? $column_keys : explode(',', $column_keys));
        $isObj = is_object(current($input));
        foreach ($input as $k => $v) {
            // 指定返回列
            if ($keys) {
                $tmp = array();
                foreach ($keys as $key) {
                    $tmp[$key] = $isObj ? $v->$key : $v[$key];
                }
                if ($isObj) $tmp = (object)$tmp;
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
     * 移除数组中指定多個欄位
     *
     * @param  Array  $input       需要取出數組列的多維數組
     * @param  String|Array $column_keys 要移除的列名，傳入陣列或逗號分隔，如不傳則返回所有列
     * @return Array
     */
    public static function except_columns(&$array, $keys)
    {
        if (!isset($keys)) return $array;

        if (!is_array($keys))
            $keys = explode(',', $keys);


        return array_diff_key($array, array_flip($keys));
    }

    /**
     * 陣列群組
     * @param array $arr
     * @param array|string|int|float|double|callable $groupKeys
     * @return void
     */
    public static function group_by(array &$arr, $groupKeys)
    {
        if (!is_array($arr)) {
            return false;
        }
        if (empty($groupKeys)) return $arr;
        if (is_object($groupKeys)) $groupKeys = (array)$groupKeys;
        if (is_string($groupKeys)) $groupKeys = explode(',', $groupKeys);
        if (is_int($groupKeys) || is_float($groupKeys) || is_double($groupKeys)) $groupKeys = [$groupKeys];

        $result = [];

        if (!is_callable($groupKeys)) {
            $groupKey = current($groupKeys);
            $secondKey = count($groupKeys) > 1 ? $groupKeys[1] : '';
            $result = array_flip(array_values(array_column($arr, $groupKey)));
            foreach ($result as $groupValue => $value) {
                $result[$groupValue] = array_values(array_filter($arr, function ($v) use ($groupKey, $groupValue) {
                    return $v[$groupKey] == $groupValue;
                }));
            }
            if (!empty($secondKey)) {
                foreach ($result as $k => $v) {
                    $result[$k] = self::group_by($result[$k], array_slice($groupKeys, 1));
                }
            }
        } else {
            foreach ($arr as $key => $value) {
                $groupKey = $groupKeys($value);
                $result[$groupKey][] = &$arr[$key];
            }
        }
        return $result;
    }

    /** filter，指定key-value
     * @param array $arr
     * @param string $keyName
     * @param string $value
     * @return array
     */
    public static function find(array $arr, $keyName = '', $value = '')
    {
        $result = array_filter($arr, function ($v) use ($keyName, $value) {
            if (is_object($v)) {
                return $v->$keyName == $value;
            }

            if (is_array($v)) {
                return $v[$keyName] == $value;
            }

            return false;
        });
        return $result;
    }


    /** 對多欄位進行排序
     * 範例 ArrExp::sort_by($array, ['a','b'=>'>','c'=>'DESC'])
     *
     * @param array $arr 資料陣列
     * @param array $sortKeyList [  'a'=>'', 'a'=>'ASC', 'b'=>'<', 'b'=>SORT_ASC] { '<' / ASC / 空白}:小到大排序，{ '>' / DESC }:大到小排序
     * @return array 回傳變更後的傳入參數$arr
     */
    public static function sort_by(array &$arr, array $sortKeyList)
    {
        if (count($sortKeyList) == 0 || count($arr) == 0) return $arr;

        $first = current($arr); // 拿來辨識物件還是陣列用，因為屬性存取的方式不一樣

        $func_setFirstOfProp = function (&$param, &$sortKey, &$sortValue, &$data) use (&$first) {

            // first of field
            if (!array_key_exists($sortKey, $param)) {
                $param[$sortKey] = [];

                // 一般文字用自然排序(數字字串不算)，其他用一般排序
                $firstValue = is_object($first) ? $first->$sortKey : $first[$sortKey];
                if (is_string($firstValue) && !is_numeric($firstValue))
                    $param[] = SORT_NATURAL;
                else
                    $param[] = SORT_REGULAR;

                // 該欄位正序倒序
                switch ($sortValue) {
                    case SORT_DESC:
                    case 'DESC':
                    case 'desc':
                    case '>':
                        $param[] = SORT_DESC;
                        break;
                    case '':
                    case SORT_ASC:
                    case 'ASC':
                    case 'asc':
                    case '<':
                    default:
                        $param[] = SORT_ASC;
                        break;
                }
            }
        };

        // 整理排序的資訊
        $sortList = [];
        $keys = array_keys((array)$first);
        foreach ($sortKeyList as $sortKey => $sortValue) {

            // 假若傳入不是key-value就轉成key-value
            if (is_int($sortKey) && is_string($sortValue)) {
                $sortKey = $sortValue;
                $sortValue = '';
            }

            // 若要排序的目標欄位不存在就拿掉
            if (in_array($sortKey, $keys))
                $sortList[$sortKey] = $sortValue;
            else
                unset($sortKeyList[$sortKey]);
        }
        $sortKeyList = &$sortList;
        if (count($sortKeyList) == 0) return $arr;

        // 建立array_multisort的參數
        $param = [];
        if (is_object($first)) {
            foreach ($sortKeyList as $sortKey => $sortValue) {
                foreach ($arr as $arrIndex => $arrValue) {
                    $func_setFirstOfProp($param, $sortKey, $sortValue, $arrValue);
                    $param[$sortKey][] = &$arrValue->$sortKey;
                }
            }
        } else {
            foreach ($sortKeyList as $sortKey => $sortValue) {
                foreach ($arr as $arrIndex => $arrValue) {
                    $func_setFirstOfProp($param, $sortKey, $sortValue, $arrValue);
                    $param[$sortKey][] = $arrValue[$sortKey];
                }
            }
        }
        $param[] = &$arr; // 最後一個放要回傳的結果

        $result = call_user_func_array('array_multisort', $param);

        return $arr;
    }

    /** 將指定屬性值作為key，若有相同的屬性值會發生覆蓋，不覆蓋請用group_by
     *
     * @param array $arr 要變更的資料陣列
     * @param string $primaryName 屬性名
     * @return array 回傳編輯完畢的$arr
     */
    public static function reset_key(array &$arr, $primaryName)
    {
        $result = [];

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (is_array($value) && array_key_exists($primaryName, $value))
                    $result[$value[$primaryName]] = $value;
                else if (is_object($value) && property_exists($value, $primaryName))
                    $result[$value->$primaryName] = $value;
                else
                    $result[] = $value;
            }
        }

        if (is_object($arr)) {
            $result[$arr->$primaryName] = $arr;
        }
        return $result;
    }

    /** 轉置陣列，將二維陣列進行轉置陣
     *
     * @param array $arr 不會變更此陣列，傳址僅為了不再重複copy一次陣列，節省記憶體空間
     * @return array
     */
    function transpose(&$arr)
    {
        // 建立轉置的陣列大小
        $rowCnt = count($arr);
        $ColCnt = count(current($arr));
        $result = array_fill(0, $ColCnt, []);
        foreach ($result as $key => $value) {
            $result[$key] =  array_fill(0, $rowCnt, '');
        }

        // 開始轉置
        for ($i = 0; $i < $rowCnt; $i++) {
            for ($j = 0; $j < $ColCnt; $j++) {
                $result[$j][$i] = $arr[$i][$j];
            }
        }

        return $result;
    }
}
