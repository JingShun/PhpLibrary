<?php

class HttpHelper
{

    public static function getUrlHander($url)
    {
        return self::curl($url)['output'];
    }

    public static function checkUrl($url, $doubleCheck = false)
    {
        $info = self::curl($url, [CURLOPT_HEADER => false], [CURLINFO_HTTP_CODE, CURLINFO_EFFECTIVE_URL, CURLINFO_TOTAL_TIME]);

        if ($doubleCheck && $info[CURLINFO_HTTP_CODE] >= 300) {
            $info = self::curl($url, [CURLOPT_HEADER => false, CURLOPT_NOBODY => false], [CURLINFO_HTTP_CODE, CURLINFO_EFFECTIVE_URL, CURLINFO_TOTAL_TIME]);
        }

        return [
            'code' => $info[CURLINFO_HTTP_CODE],
            'lastUrl' => $info[CURLINFO_EFFECTIVE_URL],
            'time' => $info[CURLINFO_TOTAL_TIME],
        ];
    }

    /**
     * 使用 print_r(GetHander('http://www.tainan.gov.tw/', [CURLINFO_HTTP_CODE, CURLINFO_EFFECTIVE_URL]));
     * 
     * 預設只取表頭
     * 
     * $getinfo:
     *  透過curl_getinfo撈指定資料 ex.CURLINFO_HTTP_CODE、CURLINFO_EFFECTIVE_URL
     *  if empty then return HEADER string, else if array then return array
     *  see. https://www.php.net/manual/zh/function.curl-getinfo.php
     * 
     */
    public static function curl($url, $curlopt = [], $getinfo = [])
    {
        $agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //講curl_exec()獲取的信息以文件流的形式返回，而不是直接輸出。
        curl_setopt($curl, CURLOPT_VERBOSE, false); // 啟用時會匯報所有的信息，存放在STDERR或指定的CURLOPT_STDERR中
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

        curl_setopt($curl, CURLOPT_HEADER, true); // 顯示表頭
        curl_setopt($curl, CURLOPT_NOBODY, true); // 忽略內容
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // 遞迴，跟著頁面跳轉
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5); //  避免無限遞迴
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //避免data資料過長問題  

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 略過檢查 SSL 憑證有效性
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 略過從證書中檢查SSL加密演算法是否存在
        //curl_setopt($curl, CURLOPT_SSLVERSION, 6); //  使用 TLS 1.2，代碼對照表可查 PHP 官網的 curl_setopt

        // 導入使用者設定
        foreach ($curlopt as $key => $value) {
            if (is_numeric($key)) {
                curl_setopt($curl, $key, $value);
            } else if (is_string($key)) {
                if (strpos($key, 'CURLOPT_') === false)
                    $key = 'CURLOPT_' . $key;
                curl_setopt($curl, constant($key), $value);
            }
        }

        $result['output'] = curl_exec($curl);
        if (count($getinfo) != 0) {
            // 取出使用者指定要的資料
            foreach ($getinfo as $key => $value) {
                $result[$value] = curl_getinfo($curl, $value);
            }
        }
        curl_close($curl);
        return $result;
    }
}

// echo '<pre>', var_dump(HttpHelper::curl(
//     'http://english.yujing.gov.tw/',
//     [
//         CURLOPT_HEADER => true,
//         CURLOPT_NOBODY => false,
//     ],
//     [CURLINFO_HTTP_CODE, CURLINFO_EFFECTIVE_URL, CURLINFO_TOTAL_TIME]
// )), '</pre>';

// echo '<pre>', var_dump(HttpHelper::checkUrl(
//     'http://english.yujing.gov.tw/' //, false
//     )
// ), '</pre>';
