<?php
//include the S3 class
if (!class_exists('S3'))require_once('S3.php');
//AWS access info
$config = Zend_Registry::get('Config');
if (!defined('awsAccessKey')) define('awsAccessKey', $config->api->amazon->aws->clientId);
if (!defined('awsSecretKey')) define('awsSecretKey', $config->api->amazon->aws->secret);
//instantiate the class

class Private_TransactionController extends Zend_Controller_Action
{
    public function allAction(){
        // Script: Update pictures from Quantups to Amazon
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $query = "picture_url LIKE '%" . BASE_URL . "%'";
        $accounts = Proxy_Account::getInstance()->retrieveByQuery($query);
        $users = Proxy_User::getInstance()->retrieveByQuery($query);

        if ($accounts->count() != 0){
            $root = ROOT . "/public/quantups_pictures/";
            $bucketName = "quantups_pictures";
            $s3->putBucket($bucketName, S3::ACL_PUBLIC_READ);
            $bucket_contents = $s3->getBucket($bucketName);
            foreach ($accounts as $account){
                $explodeUrl =  explode('/', $account->picture_url);
                $accPictureName = $explodeUrl[(sizeof($explodeUrl))-1];
                $ext = explode('.', $account->picture_url);
                $name = $account->id_user . "_" . $account->id . "_" . "account_picture";
                $pictureExt = $ext[(sizeof($ext))-1];
                $newAccPictureName =  $name . "." . $pictureExt;
                foreach(array("jpg" , "png", "gif") as $extension){
                    $url = "http://$bucketName.s3.amazonaws.com/" . $name . "." . $extension;
                    if (!$fp = curl_init($url)) {
                        return false;
                    } else {
                        $s3->deleteObject($bucketName, $name . "." . $extension);
                    }
                }
                $s3->putObjectFile($root . $accPictureName, $bucketName, $newAccPictureName, S3::ACL_PUBLIC_READ);
                $account->picture_url = "http://$bucketName.s3.amazonaws.com/" . $newAccPictureName;
                $account->save();
                unlink($root . $accPictureName);
            }
        }

        if ($users->count() != 0){
            $root = ROOT . "/public/avatars/";
            $bucketName = "quantups_avatars";
            $s3->putBucket($bucketName, S3::ACL_PUBLIC_READ);
            $bucket_contents = $s3->getBucket($bucketName);
            foreach ($users as $user){
                $explodeUrl = (explode('/', $user->picture_url));
                $userPictureName = $explodeUrl[(sizeof($explodeUrl))-1];
                $ext = explode('.', $user->picture_url);
                $name = $user->id . "_" . "avatar";
                $pictureExt = $ext[(sizeof($ext))-1];
                $newUserPictureName =  $name . "." . $pictureExt;
                foreach(array("jpg" , "png", "gif") as $extension){
                    $url = "http://$bucketName.s3.amazonaws.com/" . $name . "." . $extension;
                    if (!$fp = curl_init($url)) {
                        return false;
                    } else {
                        $s3->deleteObject($bucketName, $name . "." . $extension);
                    }
                }
                $s3->putObjectFile($root . $userPictureName, $bucketName, $newUserPictureName, S3::ACL_PUBLIC_READ);
                $user->picture_url = "http://$bucketName.s3.amazonaws.com/" . $newUserPictureName;
                $user->save();
                unlink($root . $userPictureName);
            }
        }
    }
    
    public function addAction(){
        $this-> view->pru="add";
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $resp = $s3->deleteObject("quantups_pictures", "38_4_account_picture..gif");
        var_dump($resp);
    }
    
    public function editAction(){
        $this-> view->pru="edit";
    }
    public function removeAction(){
        $this-> view->pru="remove";
    }
    public function pieAction(){
        $this-> view->pru="pie";
    }
    
}
?>
