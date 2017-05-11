<?php
namespace backend\utils;

use backend\models\Nonce;

class NonceService {
    static function logNonce($type='guid', $len=16, $function) {
        $allNonce = self::getAllDbNonce();
        do {
            $nonce = self::generateNonce($len, $type);
            // if nonce used in any db table
            if (in_array($nonce, $allNonce)) continue;
            $Nonce = D('Nonce')->where(['nonce' => $nonce, 'type'=>$type])->limit(1)->find();
        } while ($Nonce);
        //self::expireNonce($function, $function_id);
        $data = [
            'nonce' => $nonce,
            'type' => $type,
            'function' => $function,
        ];

        $Nonce = D('Nonce');
        if ($Nonce->create($data) === false) {
            $error = $Nonce->getError();
            return $error;
        }

        if ($Nonce->add()) {
            return $nonce;
        }
        return $Nonce->getError();
	}
	
	static function generateNonce($len, $type) {
        switch ($type) {
            case 'nonce':
                $nonce = Functions::nonce($len);
                break;
            case 'guid':
                $nonce = Functions::guid($len);
                break;
            default:
                $nonce = Functions::nonce($len);
                break;
        }
        return $nonce;
    }
	
	/* no use below */
	static function expireNonce($function, $function_id) {
		$expiredData = array(
			'function' => $function,
			'function_id' => $function_id,
		);
		$expiredNonce = D('Nonce')->where($expiredData)->select();
		foreach ($expiredNonce as $Nonce) {
			self::updateNonce($Nonce['nonce'], 0);
		}
	}

	static function getNonce($nonce) {
		$data = array(
			'nonce' => $nonce
		);
		$nonce = M('nonce')->where($data)->find();
		return $nonce;
	}

    /**
     * 查找目前系统中所有随机出来的数字
     * 若有新表使用 nonce，最好确定是从UI经过Model录入
     */
    static function getAllDbNonce() {
        $nsModel = 'backend\models\\';
        //banner adspace
        $banner = \backend\models\Adspace::findAll(['select' => ['guid']]);
        $banner = M('Adspace')->getField('guid', true);
        !$banner && $banner = [];
        //video adspace
        $video = M('VideoAdspace')->getField('guid', true);
        !$video && $video = [];
        //user
        $user = M('User')->getField('guid', true);
        !$user && $user = [];
        // pmp deal nonce
        $pmp = M('PmpDeal')->getField('deal_id', true);
        !$pmp && $pmp = [];
        // agent deal
        $deal = M('AgentDeal')->getField('deal_id', true);
        !$deal && $deal = [];
        $nonces = array_merge($banner, $video, $user, $pmp, $deal);
        $nonces = array_unique($nonces);
        return $nonces;
    }

	static function updateNonce($nonce, $status=0) {
		$nonce = self::getNonce($nonce);
		$nonce['status'] = $status;
		return D('Nonce')->save($nonce);
	}

    static function isNonceAlive($nonce, $type="reset password") {
        $nonceLifeSpan = self::getNonceLifeSpan($type);
        $Nonce = self::getNonce($nonce);
        $birth = $Nonce['create_time'];
        return (strtotime($birth) + $nonceLifeSpan > time()) && $Nonce['status'];
    }

    static function getNonceLifeSpan($type) {
        $nonceLifeSpan = 0;
        switch ($type) {
            case 'active account':
                $nonceLifeSpan = C('ACCOUNT_ACTIVATION_LIFE_SPAN');
                break;
            case 'reset password':
                $nonceLifeSpan = C('ACCOUNT_PASSWORD_RESET_LIFE_SPAN');
                break;
            default:
                $nonceLifeSpan = C('ACCOUNT_PASSWORD_RESET_LIFE_SPAN');
                break;
        }
        return $nonceLifeSpan;
    }
}//end
