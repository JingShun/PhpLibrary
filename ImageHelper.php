<?php


class ImageHelper
{

    /** 修正jpeg圖片因EXIF資訊而旋轉 */
    public static function FixExifRotation($path)
    {
        if (exif_imagetype($path)) {
            $exif = exif_read_data($path);
            if (!empty($exif['Orientation'])) {
                $image = imagecreatefromjpeg($path);
                switch ($exif['Orientation']) {
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                }
                imagejpeg($image, $path);
            }
        }
    }
    /** 判斷是否是常規圖片(gif/jp(e)g/png/bmp) */
    public static function IsImage($filename)
    {
        $filename = StringHelper::TransCoding($filename);

        if (!file_exists($filename)) return false;

        $mimetype = exif_imagetype($filename);
        if ($mimetype == IMAGETYPE_GIF || $mimetype == IMAGETYPE_JPEG || $mimetype == IMAGETYPE_PNG || $mimetype == IMAGETYPE_BMP) {
            return true;
        } else {
            return false;
        }
    }

    /** 圖片旋轉
     * @param string $filePath 圖片完整路徑
     * @param int $angle 旋轉角度
     * @return bool 是否成功
     * @see https : //zixuephp.net/article-409.html
     */
    public static function Rotate($filePath, $angle = 90)
    {
        //判斷圖片是否能加載
        if (self::IsImage($filePath)) {
            //獲取圖片信息
            $info  =  getimagesize($filePath);
            //獲取圖片類型
            $mime  =  $info['mime'];

            //各格式圖片資源的載入、旋轉、保存
            switch ($mime) {
                case 'image/png':
                    $source  =  imagecreatefrompng($filePath);
                    imagepng(imagerotate($source,  $angle, 0), $filePath);
                    break;

                case 'image/gif':
                    $source  =  imagecreatefromgif($filePath);
                    imagegif(imagerotate($source,  $angle, 0), $filePath);
                    break;

                case 'image/bmp':
                    $source  =  imagecreatefromwbmp($filePath);
                    imagewbmp(imagerotate($source,  $angle, 0), $filePath);
                    break;

                case 'image/jpeg':
                    $source  =  imagecreatefromjpeg($filePath);
                    imagejpeg(imagerotate($source,  $angle, 0), $filePath);
                    break;

                default:
                    return false;
            }
            return true;
        } else {
            return false;
        }
    }
}
