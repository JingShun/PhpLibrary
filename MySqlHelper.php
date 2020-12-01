<?php

/**
 * 輔助MySQLi使用。
 * @author 景舜<wewe987001@gmail.com>
 * @version 1.1.2 queryAll的str_replace換成strtr，聽說替換速度快4倍
 * @version 1.1.1 修正刪除bug
 * @version 1.1.0 queryAll調整，增加參數並預防注入攻擊
 * @version 1.0.1 平台環境是php 5.5，可變參數改用舊版本的寫法
 * @version 1.0.0
 */
class MySqlHelper
{
    /*  使用教學

    一.載入方式
        require_once($_SERVER['DOCUMENT_ROOT'] .'/sp/libraries/optional/MySqlHelper.php');

    二.使用方式，直接使用不用宣告與初始化

    1.查詢: 出來的結果自行mysqli_fetch_array
        $query = MySqlHelper::query('...sql...');

    2.查詢2: 應用在巢狀迴圈，建議用 MySqlHelper::fetch_assoc
        while($row1 = MySqlHelper::fetch_array('任意亂數值', '...sql-1...')){
            while($row2 = MySqlHelper::fetch_array('別跟上面重複就可以了', '...sql-2...')){
                //$row2
            }
        }
    查詢: 撈出全部
        MySqlHelper::queryAll('select ....')
        MySqlHelper::queryAll('select ....',[':id'=>123])

    3.插入 : 參數形式 key-value
        MySqlHelper::insert(資料表,參數)
        $result = MySqlHelper::insert('admindata', ['adminame' => '_test', 'adminpass' => 'test1']);

    4.插入 : 參數形式 欄位與值分開
        MySqlHelper::insert(資料表, 欄位陣列, 值陣列)
        $result = MySqlHelper::insert('admindata', ['adminame', 'adminpass'], ['_test', '(select 123)']);
        
    5.插入 : 值為sql(子查詢，需用括號包起來)
        MySqlHelper::insert(資料表, 欄位陣列, 值陣列)
        $result = MySqlHelper::insert('admindata', ['adminame' => '(select 123)', 'adminpass' => '(select 123)']);
    
    6 新增/更新/刪除用法一樣，key-value形式與欄位與值分開形式二選一，更新的參數先資料 後條件

    7.更新 : 參數形式 key-value
        MySqlHelper::update('admindata', ['adminpass' => '__123__'], ['adminame' => $adminame]);
         //無須條件就放空陣列
        MySqlHelper::update('admindata', ['adminpass' => '(adminpass)'], []);

    8.更新 : 參數形式 欄位與值分開
        MySqlHelper::update('admindata', ['adminpass'], ['__123__'], ['adminame'], [$adminame]);
         //無須條件就放空陣列
        MySqlHelper::update('admindata', ['adminpass'], ['(adminpass)'], [], []);


    */
    protected static $connection;
    protected static $host = 'localhost';
    protected static $dbname = 'chtsecurity_soc';
    protected static $user = 'root';
    protected static $password = 'turrteled';
    protected static $fetchStack = [];
    protected static $fetchStackSql = [];

    public function __construct($host = '', $dbname = '', $user = '', $password = '')
    {
        // 是否沒傳任何參數
        $isEmpty = array_reduce(func_get_args(), function ($v1, $v2) {
            return $v1 && $v2 === '';
        }, true);

        // 若有值就覆蓋並重設
        if (!$isEmpty)
            self::reset($host, $dbname, $user, $password);
    }

    // ==========================
    //  連接資料庫
    // ==========================

    /** 處理MySqli連接字串 */
    public static function connect()
    {
        if (!isset(self::$connection)) {
            // p: 永久連線
            self::$connection = mysqli_connect('p:' . self::$host, self::$user, self::$password, self::$dbname);

            mysqli_query(self::$connection, "SET NAMES utf8");
        }
        return self::$connection;
    }

    public static function reset($host, $dbname, $user, $password)
    {
        self::$host     = $host ?: self::$host;
        self::$dbname   = $dbname ?: self::$dbname;
        self::$user = $user ?: self::$user;
        self::$password = $password ?: self::$password;

        // 關掉先前連線
        if (isset(self::$connection))
            self::$connection->close(); // 關閉先前連線
        self::$connection = null;

        // 清空結果集
        self::clearFetchStack();

        //重新連線
        self::connect();
    }

    // ==========================
    //  資料集處理
    // ==========================
    /**
     * 清空資料集
     * @param 唯一值，預設空值代表全部清空
     */
    protected static function clearFetchStack($id = '')
    {
        if ($id === '') {
            self::$fetchStack = [];
            self::$fetchStackSql = [];
        } else if (isset(self::$fetchStack[$id])) {
            unset(self::$fetchStack[$id]);
            unset(self::$fetchStackSql[$id]);
        }
    }

    /** 取得資料集
     * @param string $id 資料集的id
     * @param string $sql 驗證用，若sql不同就重設
     */
    protected static function GetFetchStack($id, $sql)
    {
        // 如果有此id 且( 沒給sql 或是 sql一樣)，就取舊的
        if (array_key_exists($id, self::$fetchStack) && (empty($sql) || self::$fetchStackSql[$id] == $sql))
            return self::$fetchStack[$id];
        else { // 若有不同就新增
            self::$fetchStack[$id] = self::query($sql);
            self::$fetchStackSql[$id] = $sql;
            return self::$fetchStack[$id];
        }
    }
    // ==========================
    //  查詢/撈資料
    // ==========================

    /** 執行查詢，可用於較龐大的查詢(自行fetch取結果)
     * @param string $sql
     * @param bool $bigData 是否是大資料，預設false
     * @return mysqli_result|true|false 回傳
     */
    public static function query($sql, $bigData = false)
    {
        if (!$bigData)
            $query = mysqli_query(self::connect(), $sql);
        else
            $query = mysqli_query(self::connect(), $sql, MYSQLI_USE_RESULT);

        if(!$query) {
            $msg = mysqli_error(self::connect());
            echo $msg;
        }

        return $query;
    }

    /** 查詢全部資料
     * @param string $sql
     * @param array $param 要替換的內容，可預防注入攻擊
     * @param int $resulttype 預設 MYSQLI_ASSOC
     * @return array
     */
    public static function queryAll($sql, $param = [], $resulttype = MYSQLI_ASSOC)
    {
        $result = array();
        $rand = rand();

        // foreach ($param as $key => $value) {
        //     $sql = str_replace($key, self::escapeSqlString($value), $sql);
        // }
        foreach ($param as $key => $value) {
            $param[$key] = self::escapeSqlString($value);
        }
        $sql = strtr($sql, $param);

        while ($row = self::fetch_array($rand, $sql, $resulttype)) {
            $result[] = $row;
        }
        return $result;
    }

    /** 依序撈資料，提取結果行作為關聯數組，數字數組或兩者(mysqli_fetch_array)
     * @param string $id 唯一值，隨意給個亂數即可；因應可能應用在巢狀迴圈中，為避免跑錯結果集，所以用$id來分辨誰是誰
     * @param string $sql
     * @param int $resulttype 預設MYSQLI_BOTH，提取結果行作為關聯數組，數字數組或兩者
     * @return array|null
     */
    public static function fetch_array($id, $sql, $resulttype = MYSQLI_BOTH)
    {
        // 取得或新增堆疊
        $query = self::GetFetchStack($id, $sql);

        // 撈資料
        $result = mysqli_fetch_array($query, $resulttype);

        // 如果跑完了就從結果集移除
        if (!$result)
            self::clearFetchStack($id);

        return $result;
    }

    /**依序撈資料，獲取結果行作為關聯數組(mysqli_fetch_assoc)
     * @param string $id 唯一值，隨意給個亂數即可；因應可能應用在巢狀迴圈中，為避免跑錯結果集，所以用$id來分辨誰是誰
     * @param string $sql
     * @return array|null
     */
    public static function fetch_assoc($id, $sql)
    {
        return self::fetch_array($id, $sql, MYSQLI_ASSOC);
    }

    /**依序撈資料，獲取結果行作為枚舉數組(mysqli_fetch_row)
     * @param string 唯一值，隨意給個亂數即可；因應可能應用在巢狀迴圈中，為避免跑錯結果集，所以用$id來分辨誰是誰
     * @param string sql
     * @return array|null
     */
    public static function fetch_row($id, $sql)
    {
        return self::fetch_array($id, $sql, MYSQLI_NUM);
    }

    // ==========================
    //  新增/更新/刪除
    //  1.用法可選擇用key-value 或是 keys + valuies
    //  舉例：
    //      (1)欄位名稱與值配對： MySqlHelper::insert('tableName', ['field1'=>'value1', 'field2'=>'value2', ...])
    //      (2)欄位名稱與值分開： MySqlHelper::delete('tableName', ['field1', 'field2', ...], ['value1', 'value2', ...])
    //  2.更新的用法一樣，先資料 後條件
    //  舉例：
    //      (1)欄位名稱與值配對：MySqlHelper::update('tableName' ,['field1'=>'value1', 'field2'=>'value2'] ,['where3'=>'value3'] )
    //      (2)欄位名稱與值分開：MySqlHelper::update('tableName',['dataField1', 'dataField2'], ['dataValue1', 'dataValue2']',['whereField1', 'whereField2'], ['whereValue1', 'whereValue2'])
    //  3.值若是sql，請用括號包起來
    //  舉例：
    //      MySqlHelper::insert('tableName', ['field1'=>'(select 2)'])
    // ==========================

    /**
     * 新增用法
     * (1)key-value：ex. insert('tableName', ['field1'=>'value1', 'field2'=>'value2'])
     * (2)keys and valuies：ex. insert('tableName', ['field1', 'field2'], ['value1', 'value2'])
     * (3)值若是sql，請用括號包起來 ex. insert('tableName', ['field1'=>'(select 2)'])
     */
    public static function insert($table, array $keys_Or_KeyVelue, array $values = [])
    {

        // 前兩個參數必填
        if (empty($table) || !count($keys_Or_KeyVelue))  return false;
        // 若為keys + valuies模式，參數數量對不上就報錯，對地上就改為key-value形式
        if (count($values) && (count($values) != count($keys_Or_KeyVelue)))  return false;

        // key-value形式拆成 keys+valuies
        if (!count($values)) {
            $values = array_values($keys_Or_KeyVelue);
            $keys_Or_KeyVelue = array_keys($keys_Or_KeyVelue);
        }

        // 處理值的格式
        $values = array_map(function ($val) {
            return self::escapeSqlString($val);
        }, $values);

        // 組合SQL
        $sql = 'insert into ' . $table . '(' . join(',', $keys_Or_KeyVelue) . ')values(' . join(',', $values) . ')';

        return self::query($sql);
    }


    /**
     * 刪除用法
     * (1)key-value：ex. delete('tableName', ['field1'=>'value1', 'field2'=>'value2'])
     * (2)keys and valuies：ex. delete('tableName', ['field1', 'field2'], ['value1', 'value2'])
     * (3)值若是sql，請用括號包起來 ex. delete('tableName', ['field1'=>'(select 2)'])
     */
    public static function delete($table, array $keys_Or_KeyVelue, array $values = [])
    {
        // 前兩個參數必填
        if (empty($table) || !count($keys_Or_KeyVelue))  return false;
        // 若為keys + valuies模式，參數數量對不上就報錯
        if (count($values) && (count($values) != count($keys_Or_KeyVelue)))  return false;

        // 值陣列是空的表示是key-value形式，key-value形式拆成 keys+valuies
        if (!count($values)) {
            $values = array_values($keys_Or_KeyVelue);
            $keys_Or_KeyVelue = array_keys($keys_Or_KeyVelue);
        }

        // 處理值的格式
        $values = array_map(function ($val) {
            return self::escapeSqlString($val);
        }, $values);

        // 組合條件SQL
        $where = '';
        foreach ($keys_Or_KeyVelue as $index => $field) {
            $where .= $field . '=' . $values[$index] . ' and ';
        }
        if (isset($where[1])) { // 判斷第二個字元有無東西
            $where = ' where ' . substr($where, 0, -4);
        }

        // 組合SQL
        $sql = 'delete from ' . $table . $where;

        return self::query($sql);
    }

    /**
     * 更新用法，值若是sql，請用括號包起來
     * (1)key-value：ex. update('tableName' ,['field1'=>'value1', 'field2'=>'value2'] ,['where3'=>'value3'] )
     * (2)keys and valuies：ex. update('tableName',['dataField1', 'dataField2'], ['dataValue1', 'dataValue2']',['whereField1', 'whereField2'], ['whereValue1', 'whereValue2'])
     * @param string 資料表(包含join)
     * @param array 放置 資料key-value 或 資料欄位名稱
     * @param array 放置 條件key-value 或 資料內容
     * @param array 放置 條件欄位名稱
     * @param array 放置 條件內容
     */
    public static function update()
    {
        // php 5.6 之前的可變參數寫法
        $param = func_get_args();
        $table = $param[0];
        $param = array_slice($param, 1);

        // 沒給表格 與 陣列數量不對(key-value形式有兩個，欄位內容分開有4個)就離開
        if (empty($table) || count($param) <= 1 || count($param) == 3 || count($param) > 4)  return false;

        // 轉成key-value形式
        if (count($param) == 4) {
            $data = array_combine($param[0], $param[1]) ?: [];
            $where = array_combine($param[2], $param[3]) ?: [];
        } else {
            $data = &$param[0];
            $where = &$param[1];
        }

        $sql = 'update ' . $table . ' set ';

        //資料處理
        foreach ($data as $key => $value) {
            // 防注入處理
            $sql .= $key . '=' .  self::escapeSqlString($value) . ',';
        }
        $sql = substr($sql, 0, -1);

        //條件處理
        if (count($where)) {
            $sql .= ' where ';
            foreach ($where as $key => $value) {
                // 防注入處理
                $sql .= $key . '=' .  self::escapeSqlString($value) . 'and ';
            }
            $sql = substr($sql, 0, -4);
        }
        return self::query($sql);
    }

    public static function update2($table, $data=[], $where=[])
    {
        // 防呆
        if(empty($table) || !is_array($data) || !is_array($where) || count($data)==0) return false;

        $sql = 'update ' . $table . ' set ';

        //資料處理
        foreach ($data as $key => $value) {
            // 防注入處理
            $sql .= $key . '=' .  self::escapeSqlString($value) . ',';
        }
        $sql = substr($sql, 0, -1);

        //條件處理
        if (count($where)) {
            $sql .= ' where ';
            foreach ($where as $key => $value) {
                // 防注入處理
                $sql .= $key . '=' .  self::escapeSqlString($value) . 'and ';
            }
            $sql = substr($sql, 0, -4);
        }
        return self::query($sql);
    }

    // ==========================
    //  共通
    // ==========================
    /** sql字串處理，mysqli內建的防範注入攻擊 */
    protected static function escapeSqlString($val)
    {
        $val = trim($val); // 排除開頭空白

        // 這幾個開頭的不處理或特殊處理【 ( ' " ` 】
        $first = substr($val, 0, 1);
        switch ($first) {
            case '(': // sql
            case '`': // 被視為資料欄位名稱
                return $val;
            case '"':
            case '\'':
                $val = trim($val, '\'"');
            default: // 防注入
                return "'" . mysqli_real_escape_string(self::connect(), $val) . "'";
                break;
        }
    }
}
