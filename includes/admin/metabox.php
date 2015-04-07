<?php
function tcm_ui_metabox($post) {
    global $tcm;
    // Add an nonce field so we can check for it later.
    wp_nonce_field('tcm_meta_box', 'tcm_meta_box_nonce');

    $args=array('metabox'=>TRUE, 'field'=>'id');
    $ids=$tcm->Manager->getCodes(-1, $post, $args);
    $snippetsIds=$tcm->Manager->keys();
    ?>
    <div>
        <?php $tcm->Lang->P('Select existing Tracking Code')?>..
    </div>
    <input type="hidden" name="tcm_previous_ids" value="<?php echo implode(',', $ids)?>" />

    <div>
        <?php
        foreach($snippetsIds as $id) {
            $snippet=$tcm->Manager->get($id);
            $text=$snippet['name'];
            $checked='';
            if(in_array($id, $ids)) {
                $checked=' checked';
                $active=FALSE;
                if($snippet['active']) {
                    $postType=$post->post_type;
                    $active=$snippet['includePostsOfType_'.$postType.'_Active']>0;
                }
                $text='<b style="color:'.($active ? 'green' : 'red').';">'.$text.'</b>';
            }
            ?>
            <input type="checkbox" name="tcm_ids[]" value="<?php echo $id?>" <?php echo $checked ?> />
            <a href="<?php echo TCM_TAB_EDITOR_URI?>&id=<?php echo $id?>" target="_blank"><?php echo $text?></a>
            <br/>
        <?php } ?>
    </div>

    <br/>
    <?php if($tcm->Manager->rc()>0) { ?>
        <div>
            <label for="tcm_name">...<?php $tcm->Lang->P('or add a name')?></label>
            <br/>
            <input type="text" name="tcm_name" value="" style="width:100%"/>
        </div>
        <div>
            <label for="code"><?php $tcm->Lang->P('and paste HTML code here')?></label>
            <br/>
            <textarea dir="ltr" dirname="ltr" name="tcm_code" rows="10" style="font-family:Monaco,'Courier New',Courier,monospace;font-size:12px;width:100%;color:#555;background-color: #f8f8f8;"></textarea>
        </div>
    <?php } else { ?>
        <span style="color:red;font-weight:bold;"><?php $tcm->Lang->P('FreeLicenseReached')?></span>
    <?php }
}

//si aggancia per creare i metabox in post e page
add_action('add_meta_boxes', 'tcm_add_meta_box');
function tcm_add_meta_box() {
    global $tcm;

    $free=array('post', 'page');
    $options=$tcm->Options->getMetaboxPostTypes();
    $screens=array();
    foreach($options as $k=>$v) {
        if(intval($v)>0) {
            $screens[]=$k;
        }
    }
    if(count($screens)>0) {
        foreach ($screens as $screen) {
            add_meta_box(
                'tcm_sectionid'
                , $tcm->Lang->L('Tracking Code by IntellyWP')
                , 'tcm_ui_metabox'
                , $screen
                , 'side'
            );
        }
    }
}
//si aggancia a quando un post viene salvato per salvare anche gli altri dati del metabox
add_action('save_post', 'tcm_save_meta_box_data');
function tcm_save_meta_box_data($postId) {
    global $tcm;

    $postType=$_POST['post_type'];
    //in case of custom post type edit_ does not exist
    //if (!current_user_can('edit_'.$postType, $postId)) {
    //    return;
    //}

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['tcm_meta_box_nonce'])) {
        return;
    }
    // Verify that the nonce is valid.
    if (!wp_verify_nonce( $_POST['tcm_meta_box_nonce'], 'tcm_meta_box')) {
        return;
    }

    $previousIds=explode(',', $tcm->Utils->qs('tcm_previous_ids'));
    $currentIds=$tcm->Utils->qs('tcm_ids', array());
    $keyArray='PostsOfType_'.$postType;
    $keyActive=$keyArray.'_Active';

    if($previousIds!=$currentIds) {
        //first remove by ids from old snippets
        foreach($previousIds as $id) {
            $id=intval($id);
            if($id>0 && !in_array($id, $currentIds)) {
                $snippet=$tcm->Manager->get($id);
                if($snippet!=NULL) {
                    //remove my id from post type includes
                    $snippet['include'.$keyArray] = array_diff($snippet['include'.$keyArray], array($postId));
                    $snippet['include'.$keyArray] = array_unique($snippet['include'.$keyArray]);
                    $snippet['include'.$keyActive]=(count($snippet['include'.$keyArray])>0 ? 1 : 0);
                    //include it in post type exception
                    $snippet['except'.$keyArray] = array_merge($snippet['except'.$keyArray], array($postId));
                    $snippet['except'.$keyArray] = array_unique($snippet['except'.$keyArray]);
                    $snippet['except'.$keyActive]=1;
                }
                $tcm->Manager->put($id, $snippet);
            }
        }
        //after insert by id in the snippets selected
        foreach($currentIds as $id) {
            $id=intval($id);
            if($id>0 && !in_array($id, $previousIds)) {
                $snippet = $tcm->Manager->get($id);
                if ($snippet) {
                    //include my id in post type includes
                    $snippet['include'.$keyArray] = array_merge($snippet['include'.$keyArray], array($postId));
                    $snippet['include'.$keyArray] = array_unique($snippet['include'.$keyArray]);
                    $snippet['include'.$keyActive]=1;
                    //remove it from post type exception
                    $snippet['except'.$keyArray] = array_diff($snippet['except'.$keyArray], array($postId));
                    $snippet['except'.$keyArray] = array_unique($snippet['except'.$keyArray]);
                    $snippet['except'.$keyActive]=(count($snippet['except'.$keyArray])>0 ? 1 : 0);
                }
                $tcm->Manager->put($id, $snippet);
            }
        }
    }

    $name=stripslashes($tcm->Utils->qs('tcm_name'));
    $code=$tcm->Utils->qs('tcm_code');
    if($name!='' && $code!='' && !$tcm->Manager->exists($name) && $tcm->Manager->rc()>0) {
        $snippet=array(
            'active'=>1
            , 'name'=>$name
            , 'code'=>$code
        );
        $snippet['include'.$keyActive]=1;
        $snippet['include'.$keyArray]=array($postId);
        $snippet=$tcm->Manager->put('', $snippet);
        $tcm->Logger->debug("NEW SNIPPET REGISTRED=%s", $snippet);
    }
}
