<?php


class PdfHelper
{
    /**
     * PDF轉換成PNG，依賴imagick擴充套件
     * 參考來源 https://www.jb51.net/article/48264.htm
     * @param string $pdf  待處理的PDF檔案
     * @param  array $param [ 'tempPath':待儲存的圖片路徑, pref:圖片前綴詞, onlyName:是否只回傳檔名(不含路徑)]
     * @return      儲存好的圖片路徑和檔名
     */
    public static function pdf2png($pdf, array $param = ['tempPath' => '', 'pref'=>'TEMP', 'onlyName' => false])
    {

        // 處理參數
        $defaultParam = ['tempPath' => '', 'pref'=>'TEMP', 'onlyName' => false];
        $param = array_merge($defaultParam, $param);
        if($param['tempPath'] !== '' && substr($param['tempPath'], -1) != '/'){
            $param['tempPath'] += '/';
        }

        // 判斷擴展是否載入
        if (!extension_loaded('imagick')) return false;

        //判斷有無檔案
        if (!file_exists($pdf)) return false;

        $IM = new imagick();
        $IM->setResolution(120, 120);
        $IM->setCompressionQuality(80);
        $IM->readImage($pdf);
        foreach ($IM as $Key => $Var) {
            $Var->setImageFormat('png');
            $FileName = $param['pref'] . md5($Key . time()) . '.png';
            $FullName = $param['tempPath'] . $FileName;
            if ($Var->writeImage($FullName) == true) {
                $Return[] = $param['onlyName'] ? $FileName : $FullName;
            }
        }
        return $Return;
    }
}
