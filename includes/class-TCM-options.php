<?php
/**
 * Created by PhpStorm.
 * User: alessio
 * Date: 24/03/2015
 * Time: 08:45
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class TCM_Options {
    public function __construct() {

    }

    //always add a prefix to avoid conflicts with other plugins
    private function getKey($key) {
        return 'TCM_'.$key;
    }
    //option
    private function removeOption($key) {
        $key=$this->getKey($key);
        delete_option($key);
    }
    private function getOption($key, $default=FALSE) {
        $key=$this->getKey($key);
        $result=get_option($key, $default);
        if(is_string($result)) {
            $result=trim($result);
        }
        return $result;
    }
    private function setOption($key, $value) {
        $key=$this->getKey($key);
        if(is_bool($value)) {
            $value=($value ? 1 : 0);
        }
        update_option($key, $value);
    }

    //$_SESSION
    private function removeSession($key) {
        global $wp_session;

        $key=$this->getKey($key);
        if(isset($wp_session[$key])) {
            unset($wp_session[$key]);
        }
    }
    private function getSession($key, $default=FALSE) {
        global $wp_session;

        $key=$this->getKey($key);
        $result=$default;
        if(isset($wp_session[$key])) {
            $result=$wp_session[$key];
        }
        if(is_string($result)) {
            $result=trim($result);
        }
        return $result;
    }
    private function setSession($key, $value) {
        global $wp_session;

        $key=$this->getKey($key);
        $wp_session[$key]=$value;
    }

    //$_REQUEST
    //However WP enforces its own logic - during load process wp_magic_quotes() processes variables to emulate magic quotes setting and enforces $_REQUEST to contain combination of $_GET and $_POST, no matter what PHP configuration says.
    private function removeRequest($key) {
        $key=$this->getKey($key);
        if(isset($_POST[$key])) {
            unset($_POST[$key]);
        }
    }
    private function getRequest($key, $default=FALSE) {
        $key=$this->getKey($key);
        $result=$default;
        if(isset($_POST[$key])) {
            $result=$_POST[$key];
        }
        return $result;
    }
    private function setRequest($key, $value) {
        $key=$this->getKey($key);
        $_POST[$key]=$value;
    }

    //TrackingEnable
    public function isTrackingEnable() {
        return $this->getOption('TrackingEnable', 0);
    }
    public function setTrackingEnable($value) {
        $this->setOption('TrackingEnable', $value);
    }
    //TrackingNotice
    public function isTrackingNotice() {
        return $this->getOption('TrackingNotice', 1);
    }
    public function setTrackingNotice($value) {
        $this->setOption('TrackingNotice', $value);
    }

    public function getTrackingLastSend() {
        return $this->getOption('TrackingLastSend['.TCM_PLUGIN_NAME.']', 0);
    }
    public function setTrackingLastSend($value) {
        $this->setOption('TrackingLastSend['.TCM_PLUGIN_NAME.']', $value);
    }
    public function getPluginInstallDate() {
        return $this->getOption('PluginInstallDate['.TCM_PLUGIN_NAME.']', 0);
    }
    public function setPluginInstallDate($value) {
        $this->setOption('PluginInstallDate['.TCM_PLUGIN_NAME.']', $value);
    }
    public function getPluginUpdateDate() {
        return $this->getOption('PluginUpdateDate['.TCM_PLUGIN_NAME.']', 0);
    }
    public function setPluginUpdateDate($value) {
        $this->setOption('PluginUpdateDate['.TCM_PLUGIN_NAME.']', $value);
    }

    //LoggerEnable
    public function isLoggerEnable() {
        return ($this->getOption('LoggerEnable', FALSE) || (defined('TCM_LOGGER') && TCM_LOGGER));
    }
    public function setLoggerEnable($value) {
        $this->setOption('LoggerEnable', $value);
    }

    //Snippet
    public function getSnippet($id) {
        return $this->getOption('Snippet_'.$id, NULL);
    }
    public function setSnippet($id, $value) {
        $this->setOption('Snippet_'.$id, $value);
    }
    public function removeSnippet($id) {
        $this->removeOption('Snippet_'.$id);
    }
    //SnippetList
    public function getSnippetList() {
        return $this->getOption('SnippetList', array());
    }
    public function setSnippetList($value) {
        $this->setOption('SnippetList', $value);
    }
    public function removeSnippetList() {
        $this->removeOption('SnippetList');
    }

    public function hasSnippetWritten($snippet) {
        //check also the md5 of code so if the user create 2 different snippets with
        //the same tracking code we will not insert into 2 times inside the html
        $id=$snippet['id'];
        $md5=md5($snippet['code']);

        $listIds=$this->getRequest('SnippetsWrittenIds', array());
        $listMd5=$this->getRequest('SnippetsWrittenMd5', array());

        $result=(in_array($id, $listIds) || in_array($md5, $listMd5));
        return $result;
    }
    public function pushSnippetWritten($snippet) {
        $md5=md5($snippet['code']);
        $id=$snippet['id'];
        $listIds=$this->getRequest('SnippetsWrittenIds', array());
        $listMd5=$this->getRequest('SnippetsWrittenMd5', array());

        $listIds[$id]=$snippet;
        $listMd5[$md5]=$id;
        $this->setRequest('SnippetsWrittenIds', $listIds);
        $this->setRequest('SnippetsWrittenMd5', $listMd5);
    }
    public function getSnippetsWritten() {
        return $this->getRequest('SnippetsWrittenIds', array());
    }
    public function clearSnippetsWritten() {
        $this->setRequest('SnippetsWrittenIds', array());
        $this->setRequest('SnippetsWrittenMd5', array());
    }

    //PostShown
    public function getPostShown() {
        return $this->getRequest('PostShown');
    }
    public function setPostShown($post) {
        $this->setRequest('PostShown', $post);
    }
    //Cache
    public function getCache($name, $id) {
        return $this->getRequest('Cache_'.$name.'_'.$id);
    }
    public function setCache($name, $id, $value) {
        $this->setRequest('Cache_'.$name.'_'.$id, $value);
    }

    //ErrorMessages
    public function hasErrorMessages() {
        $result=$this->getRequest('ErrorMessages', NULL);
        return (is_array($result) && count($result)>0);
    }
    public function pushErrorMessage($message, $v1=NULL, $v2=NULL, $v3=NULL, $v4=NULL, $v5=NULL) {
        global $tcm;
        $array=$this->getRequest('ErrorMessages', array());
        $array[]=$tcm->Lang->L($message, $v1, $v2, $v3, $v4, $v5);
        $this->setRequest('ErrorMessages', $array);
    }
    public function writeErrorMessages($clean=TRUE) {
        global $tcm;
        $array=$this->getRequest('ErrorMessages', array());
        if(is_array($array) && count($array)>0) { ?>
            <div class="message error"><?php echo wpautop(implode("\n", $array)); ?></div>
        <?php }
        if($clean) {
            $this->removeRequest('ErrorMessages');
        }
    }
    //SuccessMessages
    public function hasSuccessMessages() {
        $result=$this->getRequest('SuccessMessages', NULL);
        return (is_array($result) && count($result)>0);
    }
    public function pushSuccessMessage($message, $v1=NULL, $v2=NULL, $v3=NULL, $v4=NULL, $v5=NULL) {
        global $tcm;
        $array=$this->getRequest('SuccessMessages', array());
        $array[]=$tcm->Lang->L($message, $v1, $v2, $v3, $v4, $v5);
        $this->setRequest('SuccessMessages', $array);
    }
    public function writeSuccessMessages($clean=TRUE) {
        $array=$this->getRequest('SuccessMessages', array());
        if(is_array($array) && count($array)>0) { ?>
            <div class="message updated"><?php echo wpautop(implode("\n", $array)); ?></div>
        <?php }
        if($clean) {
            $this->removeRequest('SuccessMessages');
        }
    }
    public function writeMessages($clean=TRUE) {
        $this->writeErrorMessages($clean);
        $this->writeSuccessMessages($clean);
    }
    public function pushMessage($success, $message, $v1=NULL, $v2=NULL, $v3=NULL, $v4=NULL, $v5=NULL) {
        if($success) {
            $this->pushSuccessMessage($message.'Success', $v1, $v2, $v3, $v4, $v5);
        } else {
            $this->pushErrorMessage($message.'Error', $v1, $v2, $v3, $v4, $v5);
        }
    }

    public function getFeedbackEmail() {
        return $this->getOption('FeedbackEmail', get_bloginfo('admin_email'));
    }
    public function setFeedbackEmail($value) {
        $this->setOption('FeedbackEmail', $value);
    }

    //MetaboxPostTypes
    public function getMetaboxPostTypes($create=TRUE) {
        global $tcm;
        $result=$this->getOption('MetaboxPostTypes', array());
        if($create) {
            $types=$tcm->Utils->query(TCM_QUERY_POST_TYPES);
            foreach($types as $v) {
                $v=$v['id'];
                if(!isset($result[$v]))  {
                    $result[$v]=1;
                }
            }
        }
        return $result;
    }
    public function setMetaboxPostTypes($values) {
        $this->setOption('MetaboxPostTypes', $values);
    }
}