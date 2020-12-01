<?php
/**
 * 收信
 */
class ReceiveMailHelper
{
    private $imap_stream =null;

    /** 初始化連線
     *
     * @param [type] $mailLink
     * @param [type] $mailUser
     * @param [type] $mailPass
     * @return imap_stream | false
     */
    function open($mailLink, $mailUser, $mailPass)
    {
        $this->imap_stream = imap_open($mailLink, $mailUser, $mailPass );
        return $this->imap_stream; //開啟信箱imap_open
    }
    
    /** 直接指定imap_stream
     *
     * @param imap_stream $stream
     * @return void
     */
    function set_stream(&$stream){
        $this->imap_stream = &$stream;
    }

    function get_conn(){
        return  $this->imap_stream;
    }

    /** TODO: 別使用這個，太慢...傳統for全部跑過還比較快
     * Undocumented function
     *
     * @param [type] $imap_stream
     * @param [type] $criteria
     * @param [type] $options
     * @param [type] $charset
     * @return array|false uid array
     */
    function search($criteria, $options = SE_FREE, $charset = "UTF-8")
    {
        return imap_search($this->imap_stream, $criteria, $options, $charset);
    }


    function get_subject($uid)
    {
        $hText = imap_fetchbody($this->imap_stream, $uid, '0', FT_UID);
        $headers = imap_rfc822_parse_headers($hText);
        return $this->decode_mime($headers->subject);
    }

    function get_body($uid)
    {
        $body = $this->get_part($this->imap_stream, $uid, "TEXT/HTML");
        // if HTML body is empty, try getting text body
        if ($body == "") {
            $body = $this->get_part($this->imap_stream, $uid, "TEXT/PLAIN");
        }
        return $body;
    }


    /*
    * decode_mime()轉換郵件標題的字元編碼,處理亂碼
    */
    function decode_mime($str)
    {
        $output = "";
        $str = imap_mime_header_decode($str);
        foreach ($str as $key => $value) {
            if ($value->charset != "default") {
                $output .= mb_convert_encoding($value->text, 'UTF-8', $value->charset);
            } else {
                $output .= $value->text;
            }
        }
        return $output;
    }
    /**
     * Undocumented function
     *
     * @param [type] $imap
     * @param [type] $uid
     * @param string $mimetype
     * @param boolean $structure
     * @param boolean $partNumber
     * @return void
     */
    function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false)
    {
        if (!$structure) {
            $structure = imap_fetchstructure($imap, $uid, FT_UID);
        }
        if ($structure) {
            if ($mimetype == $this->get_mime_type($structure)) {
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
                switch ($structure->encoding) {
                    case 3:
                        return imap_base64($text);
                    case 4:
                        return imap_qprint($text);
                    default:
                        return $text;
                }
            }

            // multipart
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = "";
                    if ($partNumber) {
                        $prefix = $partNumber . ".";
                    }
                    $data = $this->get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }

    function get_mime_type($structure)
    {
        $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

        if ($structure->subtype) {
            return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }

    /**
     * 取得附加檔案
     *
     * @param [type] $mid
     * @param [type] $path
     * @param string $filiter use regex string
     * @return void
     */
    function get_attach($mid, $path) // Get Atteced File from Mail
    {
        if (!$this->imap_stream)
            return false;
        
        $struckture = imap_fetchstructure($this->imap_stream, $mid, FT_UID);

        $files = array();
        if ($struckture->parts) {
            foreach ($struckture->parts as $key => $value) {
                $enc = $struckture->parts[$key]->encoding;

                //取郵件附件
                if ($struckture->parts[$key]->ifdparameters) {
                    //命名附件,轉碼
                    $name = $this->decode_mime($struckture->parts[$key]->dparameters[0]->value);
                    $extend = explode(".", $name);
                    $file['extension'] = $extend[count($extend) - 1];
                    $file['pathname']  = $this->setPathName($key, $file['extension']);
                    $file['title']     = !empty($name) ? htmlspecialchars($name) : str_replace('.' . $file['extension'], '', $name);
                    $file['size']      = $struckture->parts[$key]->dparameters[1]->value;
                    // $file['tmpname']   = $struckture->parts[$key]->dparameters[0]->value;
                    if (@$struckture->parts[$key]->disposition == "ATTACHMENT") {
                        $file['type']      = 1;
                    } else {
                        $file['type']      = 0;
                    }
                    $files[] = $file;

                    $message = imap_fetchbody($this->imap_stream, $mid, $key + 1, FT_UID);
                    if ($enc == 0)
                        $message = imap_8bit($message);
                    if ($enc == 1)
                        $message = imap_8bit($message);
                    if ($enc == 2)
                        $message = imap_binary($message);
                    if ($enc == 3) //圖片
                        $message = imap_base64($message);
                    if ($enc == 4)
                        $message = quoted_printable_decode($message);
                    if ($enc == 5)
                        $message = $message;
                    $fp = fopen($path . $file['pathname'], "w");
                    fwrite($fp, $message);
                    fclose($fp);
                }
                // 處理內容中包含圖片的部分
                if ($struckture->parts[$key]->parts) {
                    foreach ($struckture->parts[$key]->parts as $keyb => $valueb) {
                        $enc = $struckture->parts[$key]->parts[$keyb]->encoding;
                        if ($struckture->parts[$key]->parts[$keyb]->ifdparameters) {
                            //命名圖片
                            $name = $this->decode_mime($struckture->parts[$key]->parts[$keyb]->dparameters[0]->value);
                            $extend = explode(".", $name);
                            $file['extension'] = $extend[count($extend) - 1];
                            $file['pathname']  = $this->setPathName($key, $file['extension']);
                            $file['title']     = !empty($name) ? htmlspecialchars($name) : str_replace('.' . $file['extension'], '', $name);
                            $file['size']      = $struckture->parts[$key]->parts[$keyb]->dparameters[1]->value;
                            // $file['tmpname']   = $struckture->parts[$key]->dparameters[0]->value;
                            $file['type']      = 0;
                            $files[] = $file;

                            $partnro = ($key + 1) . "." . ($keyb + 1);

                            $message = imap_fetchbody($this->imap_stream, $mid, $partnro);
                            if ($enc == 0)
                                $message = imap_8bit($message);
                            if ($enc == 1)
                                $message = imap_8bit($message);
                            if ($enc == 2)
                                $message = imap_binary($message);
                            if ($enc == 3)
                                $message = imap_base64($message);
                            if ($enc == 4)
                                $message = quoted_printable_decode($message);
                            if ($enc == 5)
                                $message = $message;
                            $fp = fopen($path . $file['pathname'], "w");
                            fwrite($fp, $message);
                            fclose($fp);
                        }
                    }
                }
            }
        }
        //move mail to taskMailBox
        // $this->move_mails($mid, $imap_stream);

        return $files;
    }
    
    /**
     * Set path name of the uploaded file to be saved.
     *
     * @param  int    $fileID
     * @param  string $extension
     * @access public
     * @return string
     */
    public function setPathName($fileID, $extension)
    {
        return date('Ym/dHis', time()) . $fileID . mt_rand(0, 10000) . '.' . $extension;
    }
}


/* 使用範例

$mailLink = '{pop3.tainan.gov.tw:110/pop3}INBOX'; //imagp連線地址：不同主機地址不同
$mailUser = '********@mail.tainan.gov.tw'; //郵箱使用者名稱
$mailPass = '********'; //郵箱密碼

$mail = new ReceiveMailHelper();
$mbox = $mail->open($mailLink, $mailUser, $mailPass); 
$list = $mail->search($mbox, 'FROM "soc365@chtsecurity.com"', SE_UID,  "UTF-8"); // 只撈取特定寄件者
if ($list) {
    foreach ($list as $key => $uid) {

        $subject = $mail->get_subject($mbox, $uid);
        $mailBody = $mail->get_body($mbox, $uid);
        $mailBody = mb_convert_encoding($mailBody, 'UTF-8', 'BIG-5');

        echo PHP_EOL . $subject . PHP_EOL;
        echo getVal($mailBody, '事件單號') . PHP_EOL;
        echo getVal($mailBody, '派單時間') . PHP_EOL;
        break;
    }
} else {
    var_dump(imap_errors());
}


function getVal(&$data, $fieldName)
{
    if (preg_match("/$fieldName<\\/td>.*?>(.*?)<\\/td>/is", $data, $matches)) {
        return trim($matches[1]);
    }
    return '';
}


*/