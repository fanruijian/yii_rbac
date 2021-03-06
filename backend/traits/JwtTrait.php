<?php
namespace backend\traits;

use Firebase\JWT\JWT;
use Yii;
use yii\web\Request as WebRequest;
use backend\models\User;
use backend\traits\RequestTrait;
/**
 * Trait to handle JWT-authorization process. Should be attached to User model.
 * If there are many applications using user model in different ways - best way
 * is to use this trait only in the JWT related part.
 */
trait JwtTrait
{
    // use RequestTrait;
    /**
     * Store JWT token header items.
     * @var array
     */
    protected static $decodedToken;
    
    /**
     * Getter for secret key that's used for generation of JWT
     * @return string secret key used to generate JWT
     */
    protected static function getSecretKey()
    {
        return \Yii::$app->params['jwt']['secret_key'];
    }

    /**
     * Getter for "header" array that's used for generation of JWT
     * @return array JWT Header Token param, see http://jwt.io/ for details
     */
    protected static function getHeaderToken()
    {
        return \Yii::$app->params['jwt']['header_token'];
    }

    /**
     * Logins user by given JWT encoded string. If string is correctly decoded
     * - array (token) must contain 'jti' param - the id of existing user
     * @param  string $accessToken access token to decode
     * @return mixed|null          User model or null if there's no user
     * @throws \yii\web\ForbiddenHttpException if anything went wrong
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $secret = static::getSecretKey();

        // Decode token and transform it into array.
        // Firebase\JWT\JWT throws exception if token can not be decoded
        try {
            $decoded = JWT::decode($token, $secret, [static::getAlgo()]);
        } catch (\Exception $e) {
            $err = $e->getMessage();
            return $err;
        }

        static::$decodedToken = (array) $decoded;

        // If there's no jti param - exception
        if (!isset(static::$decodedToken['jti'])) {
            return false;
        }
        
        // JTI is unique identifier of user.
        // For more details: https://tools.ietf.org/html/rfc7519#section-4.1.7
        $id = static::$decodedToken['jti'];

        return true;

        // return static::findByJTI($id);
    }

    /**
     * Finds User model using static method findOne
     * Override this method in model if you need to complicate id-management
     * @param  string $id if of user to search
     * @return mixed       User model
     */
    public static function findByJTI($id)
    {
        return static::findOne($id);
    }

    /**
     * Getter for encryption algorytm used in JWT generation and decoding
     * Override this method to set up other algorytm.
     * @return string needed algorytm
     */
    public static function getAlgo()
    {
        return 'HS256';
    }

    /**
     * Returns some 'id' to encode to token. By default is current model id.
     * If you override this method, be sure that findByJTI is updated too
     * @return integer any unique integer identifier of user
     */
    public function getJTI()
    {
       $authId = $this->checkAuth();
       if($authId!=false){
        return $authId;
       }else{
        return false;
       }
    }

    /**
     * Encodes model data to create custom JWT with model.id set in it
     * @return string encoded JWT
     */
    public function getJWT()
    {
        // Collect all the data
        $secret      = static::getSecretKey();
        $currentTime = time();
        $request     = Yii::$app->request;
        $hostInfo    = '';

        // There is also a \yii\console\Request that doesn't have this property
        if ($request instanceof WebRequest) {
            $hostInfo = $request->hostInfo;
        }


        // Merge token with presets not to miss any params in custom
        // configuration
        $token = array_merge([
            'iss' => $hostInfo,     //签发者
            'aud' => $hostInfo,     //接收者
            'iat' => $currentTime,  //在什么时候签发
            'exp' => $currentTime+\Yii::$app->params['jwt']['exp_time'],  //在什么时候过期
        ], static::getHeaderToken());
        $jti = $this->getJTI();
        if(!$jti) return false;
        // Set up id
        $token['jti'] = $jti;

        return JWT::encode($token, $secret, static::getAlgo());
    }

    public function checkAuth(){
        $req = $_GET;
        if (Yii::$app->request->isPost) $req = $this->jsonObj;
        $check = null;
        if(!isset($req['email']) || !isset($req['password'])){
            return false;
        }
        $user = User::findByUsername($req['email']);
        if($user) $check = $user->validatePassword($req['password']);
        if ($user && $check) {
            $userId = $user->uid;
            return $userId;
        } else {
            return false;
        }
    }
}
