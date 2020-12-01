<?php
require_once 'ArrExp.php';

class ArrayHelper
{
    public $arr = [];
    function __construct($arr)
    {
        $this->reset($arr);
    }

    /** 重新設定陣列 */
    function reset($arr)
    {
        $this->arr = $arr;
    }

    /** 取得當前結果 */
    function result()
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
        $tmp = array_keys(current($this->arr));
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
        $this->arr = ArrExp::unique($this->arr);
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

    /**
     * 返回数组中指定多列
     * @see https://blog.csdn.net/fdipzone/article/details/78071676
     *
     * @param  string|array $column_keys 要取出的列名，逗號分隔，如不傳則返回所有列
     * @param  string $index_key   作為返回數組的索引的列
     * @return ArrayHelper
     */
    public function columns($column_keys = null, $index_key = null)
    {
        $this->arr = ArrExp::columns($this->arr, $column_keys, $index_key);
        return $this;
    }

    /**
     * 刪掉指定的欄位
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
        $this->arr = ArrExp::group_by($this->arr, $key);
        return $this;
    }

    public function sort_by($keys)
    {
        $this->arr = ArrExp::sort_by($this->arr, $keys);
        return $this;
    }

    /**
     * array_filter
     * @param callable $callback
     * @param int $flag
     * @return ArrayHelper
     */
    public function filter($callback = null, $flag = 0)
    {
        $this->arr = array_filter($this->arr, $callback, $flag);
        return $this;
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
}
