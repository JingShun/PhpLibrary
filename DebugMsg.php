<?php
/** 方便除錯的小工具
 *  作者: 景舜
 *  Date: 2019/12/17 加入error_log控制
 **/
class DebugMsg{
    private $debugMsg=array();

    // 是否同步送一份訊息到error_log
    private $_sendErrorLog = false;

    function __construct($sendErrorLog = false){
        $this->_sendErrorLog = $sendErrorLog;
    }


    function Push($data){
        $cnt = count($this->debugMsg);
        $time = microtime(true);
    
        // 計算上個指令到目前為止所花時間
        if($cnt>1){
            $this->debugMsg[$cnt -1]['cost'] = floor(($time - $this->debugMsg[$cnt -1]['time'])*10000)/10; // 毫秒ms
        }
    
        // 撈取訊息
        $msg =  is_scalar($data) ? $data : var_export($data,true);
        $this->debugMsg[]=[
            'time' => $time,
            'cost' => 0,
            'msg' => $msg
        ];

        // 輸出一份到error_log
        if($this->_sendErrorLog)
            error_log($msg);
    }

    /** 把目前的debug訊息用表格的形式顯示 */
    function ShowTable(){
        echo '<table border="1"><tr><td></td><td>Time</td><td>cost(ms)</td><td>msg</td></tr>';
        foreach ($this->debugMsg as $key => $value) {
            echo "<tr><td>$key</td><td>".$value['time'] .'</td><td>'.$value['cost'].'</td><td>'.$value['msg'].'</td></tr>';
        }
        echo '</table>';
    }
    
    /** 將debug訊息呈現再javascript的註解中 */
    function ShowByJsConsoleLog(){
        echo '<script>/*debugMsg';
        foreach ($this->debugMsg as $key => $value) {
            echo '--------------\n';
            echo "\n<[{$key}]>：time: {$value['time']} ； cost: {$value['cost']} ms ； \nmsg:{$value['msg']}";
        }
        echo '*/</script>';
    }
}