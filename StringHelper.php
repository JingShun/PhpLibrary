<?php

class StringHelper
{
    /** 數字轉國字
     * @param int $num 數字
     * @param bool $big 是否是正式的國字(壹,貳,參...)
     * @return string
     */
    public static function num2cht($num, $big = false)
    {
        $string = '';
        if ($big) {
            $numc = "零,壹,貳,參,肆,伍,陸,柒,捌,玖";
            $unic = ",拾,佰,仟";
            $unic1 = "元整,萬,億,兆,京";
        } else {
            $numc = "零,一,二,三,四,五,六,七,八,九";
            $unic = ",十,百,千";
            $unic1 = ",萬,億,兆,京";
        }
        $numc_arr = explode(",", $numc);
        $unic_arr = explode(",", $unic);
        $unic1_arr = explode(",", $unic1);
        $i = str_replace(",", "", $num);
        $c0 = 0;
        $str = array();
        do {
            $aa = 0;
            $c1 = 0;
            $s = "";
            $lan = (strlen($i) >= 4) ? 4 : strlen($i);
            $j = substr($i, -$lan);
            while ($j > 0) {
                $k = $j % 10;
                if ($k > 0) {
                    $aa = 1;
                    $s = $numc_arr[$k] . $unic_arr[$c1] . $s;
                } elseif ($k == 0) {
                    if ($aa == 1) $s = "0" . $s;
                }
                $j = intval($j / 10);
                $c1 += 1;
            }
            $str[$c0] = ($s == '') ? '' : $s . $unic1_arr[$c0];
            $count_len = strlen($i) - 4;
            $i = ($count_len > 0) ? substr($i, 0, $count_len) : '';
            $c0 += 1;
        } while ($i != '');
        foreach ($str as $v) $string .= array_pop($str);
        $string = preg_replace('/0+/', $numc_arr[0], $string);
        return $string;
    }


    /** 將指定的 utf-8 字串編碼轉換成當前環境編碼 */
    public static function TransCoding($str)
    {
        // 環境 windows 10 -> big5
        // 環境 ubuntu is -> utf8 (預設已是utf8，無需處理)
        $detect = self::GetCoding($str);

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') { // && self::IsUTF8($str)
            // 環境 windows 10 -> big5
            if ($detect != 'BIG-5')
                return mb_convert_encoding($str, "big5", $detect); //,"UTF-8"
        }else{
            // 環境 ubuntu -> UTF-8
            if ($detect != 'UTF-8')
                return mb_convert_encoding($str, "UTF-8", $detect); //,"UTF-8"
        }
        // 不用轉碼
        return $str;
    }

    /** 判斷字串是否是utf8
     * https://www.itread01.com/p/941781.html
     */
    public static function IsUTF8($liehuo_net)
    {
        if (preg_match("/^([" . chr(228) . "-" . chr(233) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}){1}/", $liehuo_net) == true || preg_match("/([" . chr(228) . "-" . chr(233) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}){1}$/", $liehuo_net) == true || preg_match("/([" . chr(228) . "-" . chr(233) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}){2,}/", $liehuo_net) == true) {
            return true;
        } else {
            return false;
        }
    }

    /** 取得文字編碼
     */
    public static function GetCoding($str)
    {
        return mb_detect_encoding($str, ['UTF-8', 'BIG5', 'ASCII', 'GB2312', 'GBK']);
    }
}
