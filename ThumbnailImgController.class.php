<?php
namespace Home\Controller;
use OT\DataDictionary;
use Think\Log;

class ThumbnailImgController extends HomeController {
	//减小身份证图片大小
	// http://dev.dgdgdgdg.com/Thetool/thumbnailIdcardImg/filename/Card
    public function thumbnailIdcardImg($filename){
    	if (empty($filename)) {
    		$this->error('filename没填');
    	}

		$image = new \Think\Image(); 

    	$dir = opendir(APP_PATH.'../Uploads/'.$filename);
		
		while (($file = readdir($dir)) !== false)
		{
	    	// 文件小于1M就不搞了
			$size=filesize(APP_PATH.'../Uploads/'.$filename.'/'.$file);
			if ($size>800000) {
				$image->open(APP_PATH.'../Uploads/'.$filename.'/'.$file);
				$width = $image->width(); // 返回图片的宽度
				$height = $image->height(); // 返回图片的高度
				$image->thumb($width/2, $height/2,\Think\Image::IMAGE_THUMB_FIXED)->save(APP_PATH.'../Uploads/'.$filename.'/'.$file);
			}
		}

		closedir($dir);
	}
	// http://dev.dgdgdgdg.com/Thetool/thumbnailPhotoImg/path/Picture
	public function thumbnailPhotoImg($path = '.') {
		$image = new \Think\Image(); 
		
		$current_dir = opendir(APP_PATH.'../Uploads/'.$path);
		 while(($file = readdir($current_dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
		     $sub_dir = APP_PATH.'../Uploads/'.$path.DIRECTORY_SEPARATOR.$file;    //构建子目录路径
		     if($file == '.' || $file == '..') {
		         continue;
		     } else if(is_dir($sub_dir)) {    //如果是目录,进行递归
		        $this->thumbnailPhotoImg($path.DIRECTORY_SEPARATOR.$file);
		        echo $file;
		     } else {    //如果是文件,直接输出
				$size=filesize($sub_dir);
				if ($size>60000) {
					$image->open($sub_dir);
					$width = $image->width(); // 返回图片的宽度
					$height = $image->height(); // 返回图片的高度
					$image->thumb($width/2, $height/2,\Think\Image::IMAGE_THUMB_FIXED)->save(APP_PATH.'../Uploads/'.$path.DIRECTORY_SEPARATOR.$file);
				}
				echo $path.'---'.$file.'</br>';
		     }
		 }
	}
}