<?php
/**
 * 檔案名稱：TxtFileHelper
 * 用途：處理純文字檔案(ex. csv)
 */

/** 匯出產生csv檔
 * @see https://codertw.com/程式語言/215402/
 * @param string $data 已經整理好逗號換行的純文字
 */
function export_csv($filename, $data)
{
    header("Content-type:text/csv");
    header("Content-Disposition:attachment;filename=" . $filename);
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    echo $data;
}

/** 移除UTF-8的 BOM 檔頭 */
function removeBOM($str = '')
{
    if (substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf))
        $str = substr($str, 3);

    return $str;
}

/** 產生utf8 含BOM檔頭的檔案 */
function writeUTF8File($filename,$content)
{
    $f = fopen($filename, 'w');
    fwrite($f, pack("CCC", 0xef,0xbb,0xbf));
    fwrite($f,$content);
    fclose($f);
}
