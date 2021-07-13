<?php
require_once 'ArrExp.php';

/**
 *  * @version 0.3.2 fixbug.sum重構，修正一些情況sum回傳0的問題
 *  * @version 0.3.1 bug修正
 *  * @version 0.3.0 功能擴充，可直接靜態呼叫 ArrayHelper::from($data)->... 
 *  * @version 0.2.0 功能擴充，加入select / reset_index功能，有望替換YaLinqo套件
 */
class ArrayHelper
{
    public $arr = [];
    function __construct($arr)
    {
        $this->reset($arr);
    }

    public static function from($arr)
    {
        return new ArrayHelper($arr);
    }

    /** 重新設定陣列 */
    public function reset($arr)
    {
        $this->arr = $arr ?: [];
    }

    /** 取得當前結果 */
    public function result()
    {
        return $this->arr;
    }

    /** 取得當前結果，轉成html表格 */
    function resultToTable($headerMap = [])
    {
        $html = '';
        $html .= '<table border="1" width="100%" align="center">';

        // 顯示標題
        $html .=  '<tr bgcolor="#dddddd">';
        $tmp = array_keys((array)current($this->arr));
        foreach ($tmp as $mn) {
            $html .=  "<th>{$mn}</th>";
        }
        $html .=  '</tr>';

        // 內文
        foreach ($this->arr as $key => $value) {
            $html .=  '<tr>';
            foreach ($value as $mn) {
                $html .=  "<td>{$mn}</td>";
            }
            $html .=  '</tr>';
        }
        $html .=  '</table>';
        return $html;
    }

    /**
     * @return ArrayHelper
     */
    public function unique()
    {
        $this->arr = ArrExp::unique($this->arr) ?: [];
        return $this;
    }

    public function toArrayList()
    {
        foreach ($this->arr as $key => $value) {
            $this->arr[$key] = (array)$this->arr[$key];
        }
        return $this;
    }
    public function toObjectList()
    {
        foreach ($this->arr as $key => $value) {
            $this->arr[$key] = (object)$this->arr[$key];
        }
        return $this;
    }

    /** 返回数组中指定多列
     * @see https://blog.csdn.net/fdipzone/article/details/78071676
     *
     * @param  string|array $column_keys 要取出的列名，逗號分隔，如不傳則返回所有列
     * @param  string $index_key   作為返回數組的索引的列
     * @return ArrayHelper
     */
    public function columns($column_keys = null, $index_key = null)
    {
        $this->arr = ArrExp::columns($this->arr, $column_keys, $index_key) ?: [];
        return $this;
    }

    /** 刪掉指定的欄位
     * @see https://blog.csdn.net/fdipzone/article/details/78071676
     *
     * @param  string|array $column_keys 要取出的列名，逗號分隔，如不傳則返回所有列
     * @return ArrayHelper
     */
    public function except_columns($column_keys = null)
    {
        foreach ($this->arr as $key => $value) {
            $this->arr[$key] = ArrExp::except_columns($value, $column_keys);
        }
        return $this;
    }

    /**
     * @param mixed|array $key,... A set of keys to group by.
     * @return ArrayHelper
     */
    public function group_by($key)
    {
        $this->arr = ArrExp::group_by($this->arr, $key) ?: [];
        return $this;
    }

    /** 對多欄位進行排序
     * 範例:(new ArrayHelper($data))->sort_by(['a'=>SORT_DESC, 'c', 'b'])->resultToTable();
     * @param array $keys [ 'a', 'b'=>'ASC', 'b'=>'<', 'b'=>SORT_ASC] 說明:{ '<' / ASC / 空白}:小到大排序，{ '>' / DESC }:大到小排序
     * @return ArrayHelper
     */
    public function sort_by($keys)
    {
        ArrExp::sort_by($this->arr, $keys);
        return $this;
    }

    /**
     * array_filter
     * @param callable $callbackOrArr
     * @param int $flag
     * @return ArrayHelper
     */
    public function filter($callbackOrArr = null, $flag = 0)
    {
        if ($callbackOrArr == null) {
            $callbackOrArr = function ($v) {
                return $v;
            };
        }
        $this->arr = array_filter($this->arr, $callbackOrArr, $flag) ?: [];
        return $this;
    }

    /** filter，指定單組key-value
     * @param string $keyName
     * @param string $value
     * @return ArrayHelper
     */
    public function find($keyName = '', $value = '')
    {
        $this->arr = ArrExp::find($this->arr, $keyName, $value) ?: [];
        return $this;
    }
    /** filter，指定單組key-value
     * @param string $keyName
     * @param string $value
     * @return ArrayHelper
     */
    public function where($keyName = '', $value = '')
    {
        return $this->find($keyName, $value);
    }

    public function group_concat($glue = ',', $distinct = false)
    {

        // 建立暫存的陣列
        $out = current($this->arr);
        foreach ($out as $key => $value) {
            $out[$key] = [];
        }

        // 各屬性都先放一起方便後續作業
        foreach ($this->arr as $item) {
            // 開始處理每個屬性
            foreach ($item as $key => $value) {
                $out[$key][] = $value;
            }
        }

        // 依需求進行處理
        foreach ($out as $key => $value) {

            // 排除重複
            if ($distinct)
                $out[$key] = ArrExp::unique($value);

            // 合併成單一字串
            $out[$key] = join($glue, $out[$key]);
        }
        $this->arr = &$out;
        return $this;
    }


    /** 將指定屬性值作為key，若有相同的屬性值會發生覆蓋，不覆蓋請用group_by
     *
     * @param array $arr 要變更的資料陣列
     * @param string $primaryName 屬性名
     * @return ArrayHelper
     */
    public function reset_key($primaryName)
    {
        $this->arr = ArrExp::reset_key($this->arr, $primaryName) ?: [];
        return $this;
    }

    /** 重設(移除)索引
     * @return ArrayHelper
     */
    public function reset_index()
    {
        $this->arr = array_values($this->arr) ?: [];
        return $this;
    }


    /** count
     * @return int
     */
    public function count()
    {
        return count($this->arr);
    }

    /** 將整個矩陣內容加起來
     * @param string|array $keyName 限制欄位
     * @return int|float|mixed
     */
    public function sum($keyName = null)
    {
        if (!empty($keyName)) {
            $this->columns($keyName);
        }
        
        $sum = 0;
        foreach ($this->arr as $fields) {
            if(is_array($fields)){
                foreach ($fields as $value) {
                    $sum += is_numeric($value) ? $value : 0;
                }
            }
            else {
                $sum += is_numeric($fields) ? $fields : 0;
            }
        }
        return $sum;
    }

    /** 自定資料結構，傳入什麼型態就變更什麼型態
     *
     * @param callable|array|string $callbackOrArr 傳入要匯出的欄位(同columns)或自訂方法
     * @return ArrayHelper
     */
    public function select($callbackOrArr)
    {
        if (is_callable($callbackOrArr)) {
            $this->arr = array_map(
                function ($v) use ($callbackOrArr) {
                    return $callbackOrArr($v);
                },
                $this->arr
            ) ?: [];
            return $this;
        }

        $isObj = is_object(current($this->arr));
        $isStr = is_string($callbackOrArr);
        $oneKeyName = $isStr && strpos($callbackOrArr, ',') === false;

        if ($isStr) {
            if ($oneKeyName) {
                $this->arr = array_column($this->arr, $callbackOrArr) ?: [];
                return $this;
            } else
                $callbackOrArr = explode(',', $callbackOrArr);
        }

        $this->columns($callbackOrArr);

        if ($isObj) $this->toObjectList();
        // else $this->arr = array_values($this->arr); // 重設索引

        return $this;
    }


    public function first()
    {
        return current($this->arr);
    }

    /** 取出指定數量
     * @param int $num
     * @return ArrayHelper
     */
    public function take($num)
    {
        $this->arr = array_slice($this->arr, 0, $num) ?: [];
        return $this;
    }

    /** array_slice，從第$start個開始(起始0)，取$length
     *
     * @param int $start 起始索引(0開始)
     * @param int|null $length 取出數量
     * @return ArrayHelper
     */
    public function slice($start = 0, $length  = null)
    {
        $this->arr = array_slice($this->arr, $start, $length) ?: [];
        return $this;
    }


    /** 取出指定屬性值
     *
     * @param string|null $keyName 若為null則同first()
     * @return mixed
     */
    public function getVal($keyName = null, $default = null)
    {
        if (count($this->arr) > 1)
            trigger_error('ArrayHelper::getVal()有超過1筆資料要選哪筆?', E_USER_WARNING);

        if (is_array($this->arr)) {
            $item = current($this->arr);

            if (!is_null($keyName) && (is_array($item) || is_object($item))) {
                $item = (array)$item;
                return  $this->ifnull($item[$keyName], $default);
            } else
                return $this->ifnull($item, $default);
        }
        return $this->ifnull($this->arr, $default);
    }

    function ifNull(&$val, &$default)
    {
        return !is_null($val) ? $val :  $default;
    }
    function ifEmpty(&$val, &$default)
    {
        return !empty($val) ? $val :  $default;
    }
}
