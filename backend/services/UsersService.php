<?php
namespace app\services;

use app\models\User;

class UsersService extends BaseService {
    private $defaultPassword = '123456';
    public $matrix = [
        'id' => 'id',
        'name' => 'name',
        'type' => ['user_type', '$v==1 && $v="root";
                                 $v==2 && $v=\Yii::t("common", "Operator");
                                 $v==3 && $v=\Yii::t("common", "Agent");
                                 return $v;'],
        'email' => 'email',
        'resource' => 'resource',
        'status' => ['status', '$v==0 && $type=\Yii::t("common", "Pending");
                                $v==1 && $type=\Yii::t("common", "Normal");
                                $v==2 && $type=\Yii::t("common", "Normal");
                                return $type;'],
        'lastModifiedDate' => 'last_update',
        'operate' => ['id', 'return "edit,view";'],
    ];

    public function checkNewPassword($password, $confirm) {
        if (empty($password)) {
            $this->NG($this->t('common', 'Password cannot be empty!'));
        } else if ($password != $confirm) {
            $this->NG($this->t('common', 'Confirm password not match password!'));
        }
        return true;
    }

    public function saveNewUser($data, $isNew=true) {
        $data['status'] = 1;
        if ($isNew) {
            $this->checkNewPassword($data['password'], $data['confirm_password']);
            $this->I('password', $this->cryptPassword($data['password']));
        }
        // 新添加用户没有 id
        //$this->I('id', null);

        // $_POST['adnetwork_slot'] = '1';
        // $_POST['traffic_slot'] = implode(',', $this->I('traffic_slot'));
        // $_POST['media_slot'] = implode(',', $this->I('media_slot'));

        if (in_array(0, $this->I('adnetwork_slot'))) {
             $_POST['adnetwork_slot'] = '0';
        } else {
             $_POST['adnetwork_slot'] = implode(',', $this->I('adnetwork_slot'));
        }
        
        if (in_array(0, $this->I('traffic_slot'))) {
             $_POST['traffic_slot'] = '0';
        } else {
             $_POST['traffic_slot'] = implode(',', $this->I('traffic_slot'));
        }

        if (in_array(0, $this->I('media_slot'))) {
             $_POST['media_slot'] = '0';
        } else {
             $_POST['media_slot'] = implode(',', $this->I('media_slot'));
        }

        $newUser = $this->save('User', $data);
        $resource = ['user_id' => $newUser['id']];

        $this->I('id', null);
        $this->trySaveUserResource($newUser['id'], $users, 1);
    }

    public function updatePassword($password, $confirm, $userId='')
    {
        if($this->checkNewPassword($password, $confirm)) {
            !$userId && $userId = $this->S('user_id');
            return $this->saveOn('User', ['id' => $userId], ['password' => $this->cryptPassword($password)]);
        }
        return false;
    }

    public function cryptPassword($password) {
        $salt = $this->randString(2);
        return crypt($password, $salt);
    }

    public function validatePassword($password, $dbPassword) {
        return crypt($password, $dbPassword)===$dbPassword;
    }

    public function resetPassword($userId) {
        $password = $this->defaultPassword;
        $confirm = $password;
        return $this->updatePassword($password, $confirm, $userId);
    }

    public function chkLogin($useremail, $password) {
        $user = User::find()->where(['name' => $useremail])->one();
        if ($user) {
            if($this->validatePassword($password, $user->password)) {
                return $this->writeSession($user);
            }
            return false;
            //$this->NG($this->t('common', 'Password not correct!'));
        }
        return false;
        //$this->NG($this->t('common', 'Email not registed!'));
    }

    public function getUserRank($user) {
        if ($user['user_type'] == 1) return 'root';
        if ($user['user_type'] == 2) return 'admin';
        if ($user['user_type'] == 3) return 'rd';
    }

    public function writeSession($user) {
        $this->S('user_type', $user['type']);
        $this->S('user_role', $user['role']);
        $this->S('user_name', $user['name']);
        $this->S('user_id', $user['id']);
        $this->S('user_email', $user['email']);
        $this->S('LastRequestTime', time());

        return true;
    }

    /**
     * 刷新当前用户的 session
     */
    public function freshSession() {
        $user = $this->M('User', $this->S('user_id'));
        $this->writeSession($user);
    }

    public function getAllUsers() {
        $datas = $this->getAll('User');
        $advertisers = $this->getAll('Advertiser');
        foreach ($datas as &$user) {
            $user['resource'] = '';
            $rank = $this->getUserRank($user); 
            if ($rank == 'root') {
                $allAd = $this->t('common', 'All Advertiser');
                $resource = [$allAd];
            } else {
                $ads = [];
                $resource = '';
                $userResource = $this->getAll('UserResource', ['status' => 1]);
                $userResource = $this->arrayGroup($userResource, 'user_id');
                $adIds = $this->getUserSubIds($user['id'], $userResource);
                foreach ($advertisers as $ad) {
                    if (in_array($ad['id'], $adIds)) {
                        $ads[] = $ad['name'];
                    }
                }
                $resource = implode(', ', $ads);
            }
            $user['resource'] = $resource;
        }
        return $this->fieldsMap($this->matrix, $datas);
    }
}// end of user service declaration
