<?php
namespace backend\services;
/*
 * 创意上传接口
 */

class UploadService extends BaseService {
	private $config = null;

	public function __construct($config=[]) {
		parent::__construct();
		$this->init($config);
	}

	public function init($config=[]) {
		if (empty($config)) {
			$config = \Yii::$app->params['upload'];
		}
		$this->config = $config;
	}

	// ajax 上传文件
	public function ajaxUpload($file, $type=1) {
		$orignName = $file['name'][0];
        $nameWithoutExt = explode('.', $orignName);
        array_pop($nameWithoutExt);
        $nameWithoutExt = implode('.', $nameWithoutExt);
        $fileName = date("YmdHis").'_'.rand(100,999).'_';

        $upload = new \Think\Upload(C('upload'));
        $upload->saveName = $fileName.$nameWithoutExt;

        if(!$upload->upload()) {
            return false;
        } else {
			$File = D('Upload');
			$creator = '10000000';
			session('guid') && $creator = session('guid');
			$File->create(['name'		 => $orignName,
						   'upload_name' => $fileName.$orignName,
						   'creator' 	 => $creator,
						   'upload_ip'	 => get_client_ip(),
                           'upload_file_type' => $type,
						   'size'		 => $file['size'][0]]);
			$id = $File->add();
            return $id;
        }
    }

}//end of UploadService
