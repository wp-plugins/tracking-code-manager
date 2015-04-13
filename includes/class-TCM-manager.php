<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class TCM_Manager {

    public function __construct() {

    }

    public function exists($name) {
        $snippets = $this->values();
        $result = NULL;
        $name=strtoupper($name);
        if (isset($snippets[$name])) {
            $result=$snippets[$name];
        }
        return $result;
    }

    //get a code snippet
    public function get($id, $new = FALSE) {
        global $tcm;

        $snippet=$tcm->Options->getSnippet($id);
        if (!$snippet && $new) {
            $snippet=array();
            $snippet['active']=1;
            $snippet['includeEverywhereActive']=1;
        }

        $snippet=$this->sanitize($id, $snippet);
        return $snippet;
    }

    private function sanitize($id, $snippet) {
        global $tcm;
        if($snippet==NULL || !is_array($snippet)) return;

        $defaults=array(
            'id'=>$id
            , 'active'=>0
            , 'name'=>''
            , 'code'=>''
            , 'position'=>TCM_POSITION_HEAD
            , 'includeEverywhereActive'=>0
        );

        $types=$tcm->Utils->query(TCM_QUERY_POST_TYPES);
        foreach($types as $v) {
            $defaults['includePostsOfType_'.$v['name'].'_Active']=0;
            $defaults['includePostsOfType_'.$v['name']]=array();
            $defaults['exceptPostsOfType_'.$v['name'].'_Active']=0;
            $defaults['exceptPostsOfType_'.$v['name']]=array();
        }
        $snippet=$tcm->Utils->parseArgs($snippet, $defaults);
        //$snippet['includeLastPosts'] = intval($snippet['includeLastPosts']);

        foreach ($snippet as $k => $v) {
            if (stripos($k, 'active') !== FALSE) {
                $snippet[$k]=intval($v);
            } elseif (is_array($v)) {
                switch ($k) {
                    /*
                    case 'includePostsTypes':
                    case 'excludePostsTypes':
                        //keys are string and not number
                        $result = $this->uarray($snippet, $k, FALSE);
                        break;
                    */
                    default:
                        //keys are number
                        $result = $this->uarray($snippet, $k, TRUE);
                        break;
                }
            }
        }
        $snippet['code']=trim($snippet['code']);
        $snippet['position']=intval($snippet['position']);

        $code=strtolower($snippet['code']);
        $cnt=substr_count($code, '<iframe')+substr_count($code, '<script');
        if($cnt<=0) {
            $cnt=1;
        }
        $snippet['codesCount']=$cnt;
        return $snippet;
    }
    private function uarray($snippet, $key, $isInteger = TRUE) {
        $array = $snippet[$key];
        if (!is_array($array)) {
            $array = explode(',', $array);
        }

        if ($isInteger) {
            for ($i = 0; $i < count($array); $i++) {
                $array[$i] = intval($array[$i]);
            }
        }

        $array = array_unique($array);
        $snippet[$key] = $array;
        return $snippet;
    }

    public function rc() {
        global $tcm;
        $result = 6-$this->codesCount();
        return $result;
    }

    //add or update a snippet (html tracking code)
    public function put($id, $snippet) {
        global $tcm;

        if ($id == '' || intval($id) <= 0) {
            //if is a new code create a new unique id
            $id = $this->getLastId() + 1;
            $snippet['id'] = $id;
        }
        $snippet=$this->sanitize($id, $snippet);
        $tcm->Options->setSnippet($id, $snippet);

        $keys = $this->keys();
        if (is_array($keys) && !in_array($id, $keys)) {
            $keys[] = $id;
            $this->keys($keys);
        }
        return $snippet;
    }

    //remove the id snippet
    public function remove($id) {
        global $tcm;
        $tcm->Options->removeSnippet($id);
        $keys=$this->keys();
        $result = FALSE;
        if (is_array($keys) && in_array($id, $keys)) {
            $keys = array_diff($keys, array($id));
            $this->keys($keys);
            $result = TRUE;
        }
        return $result;
    }

    //verify if match with this snippet
    private function matchSnippet($postId, $postType, $categoriesIds, $tagsIds, $prefix, $snippet) {
        global $tcm;

        $include=FALSE;
        $postId=intval($postId);
        if($postId>0) {
            $what=$prefix.'PostsOfType_'.$postType;
            if(!$include && $snippet[$what.'_Active'] && $tcm->Utils->inArray($postId, $snippet[$what])) {
                $tcm->Logger->debug('MATCH=%s SNIPPET=%s[%s] DUE TO POST=%s OF TYPE=%s IN [%s]'
                    , $prefix, $snippet['id'], $snippet['name'], $postId, $postType, $snippet[$what]);
                $include=TRUE;
            }
        }

        return $include;
    }

    public function writeCodes($position) {
        global $tcm;

        $text='';
        switch ($position) {
            case TCM_POSITION_HEAD:
                $text='HEAD';
                break;
            case TCM_POSITION_BODY:
                $text='BODY';
                break;
            case TCM_POSITION_FOOTER:
                $text='FOOTER';
                break;
        }

        $post=$tcm->Options->getPostShown();
        $args=array('field'=>'code');
        $codes=$tcm->Manager->getCodes($position, $post, $args);
        if(is_array($codes) && count($codes)>0) {
            echo "\n<!--BEGIN: TRACKING CODE MANAGER BY INTELLYWP.COM IN $text//-->";
            foreach($codes as $v) {
                echo "\n$v";
            }
            echo "\n<!--END: TRACKING CODE MANAGER BY INTELLYWP.COM IN $text//-->";
        }
    }

    //from a post retrieve the html code that is needed to insert into the page code
    public function getCodes($position, $post, $args=array()) {
        global $tcm;

        $defaults=array('metabox'=>FALSE, 'field'=>'code');
        $args=wp_parse_args($args, $defaults);

        $postId=0;
        $postType='page';
        $tagsIds=array();
        $categoriesIds=array();
        if($post) {
            $postId = $post->ID;
            $postType = $post->post_type;
        }

        $tcm->Options->clearSnippetsWritten();
        $keys=$this->keys();
        foreach ($keys as $id) {
            $v=$this->get($id);
            if(!$v || ($position>-1 && $v['position']!=$position) || $v['code']=='' || (!$args['metabox'] && !$v['active'])) {
                continue;
            }
            if($tcm->Options->hasSnippetWritten($v)) {
                $tcm->Logger->debug('SKIPPED SNIPPET=%s[%s] DUE TO ALREADY WRITTEN', $v['id'], $v['name']);
                continue;
            }

            $match=FALSE;
            if(!$match && $v['includeEverywhereActive']) {
                $tcm->Logger->debug('INCLUDED SNIPPET=%s[%s] DUE TO EVERYWHERE', $v['id'], $v['name']);
                $match=TRUE;
            }
            if(!$match && $postId>0 && $this->matchSnippet($postId, $postType, $categoriesIds, $tagsIds, 'include', $v)) {
                $match=TRUE;
            }

            if($match && $postId>0) {
                if($this->matchSnippet($postId, $postType, $categoriesIds, $tagsIds, 'except', $v)) {
                    $tcm->Logger->debug('FOUND AT LEAST ON EXCEPT TO EXCLUDE SNIPPET=%s [%s]', $v['id'], $v['name']);
                    $match=FALSE;
                }
            }

            if ($match) {
                $tcm->Options->pushSnippetWritten($v);
            }
        }

        //obtain result as snippets or array of one field (tipically "id")
        $result=$tcm->Options->getSnippetsWritten();
        if ($args['field']!='all') {
            $array=array();
            foreach($result as $k=>$v) {
                $k=$args['field'];
                if(isset($v[$k])) {
                    $array[]=$v[$k];
                } else {
                    $tcm->Logger->error('SNIPPET=%s [%s] WITHOUT FIELD=%s', $v['id'], $v['name'], $k);
                }
            }
            $result=$array;
        }
        return $result;
    }

    //ottiene o salva tutte le chiavi dei tracking code utilizzati ordinati per id
    public function keys($keys=NULL) {
        global $tcm;

        if (is_array($keys)) {
            $tcm->Options->setSnippetList($keys);
            $result=$keys;
        } else {
            $result=$tcm->Options->getSnippetList();
        }

        if (!is_array($result)) {
            $result = array();
        } else {
            sort($result);
        }
        return $result;
    }

    //ottiene il conteggio attuale dei tracking code
    public function count() {
        $result = count($this->keys());
        return $result;
    }
    public function codesCount() {
        $result=0;
        $ids=$this->keys();
        foreach($ids as $id) {
            $snippet=$this->get($id);
            if($snippet) {
                if($snippet['codesCount']>0) {
                    $result+=intval($snippet['codesCount']);
                } else {
                    $result+=1;
                }
            }
        }
        return $result;
    }
    public function getLastId() {
        $result = 0;
        $list = $this->keys();
        foreach ($list as $v) {
            $v = intval($v);
            if ($v > $result) {
                $result = $v;
            }
        }
        return $result;
    }

    //ottiene tutti i tracking code ordinati per nome
    public function values()  {
        $keys = $this->keys();
        $result = array();
        foreach ($keys as $k) {
            $v = $this->get($k);
            $result[strtoupper($v['name'])] = $v;
        }
        ksort($result);
        return $result;
    }
}