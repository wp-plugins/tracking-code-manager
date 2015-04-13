<?php
function tcm_ui_editor() {
    global $tcm;

    $tcm->Form->prefix = 'Editor';
    $id = intval($tcm->Utils->qs('id', 0));
    $action = $tcm->Utils->qs('action');
    $snippet = $tcm->Manager->get($id, TRUE);
    //var_dump($snippet);

    if (wp_verify_nonce($tcm->Utils->qs('tcm_nonce'), 'tcm_nonce')) {
        //var_dump($_POST);
        //var_dump($_GET);
        foreach ($snippet as $k => $v) {
            $snippet[$k] = $tcm->Utils->qs($k);
            if (is_string($snippet[$k])) {
                $snippet[$k] = stripslashes($snippet[$k]);
            }
        }

        if ($snippet['name'] == '') {
            $tcm->Options->pushErrorMessage('Please enter a unique name');
        } else {
            $exist=$tcm->Manager->exists($snippet['name']);
            if ($exist && $exist['id'] != $snippet['id']) {
                //nonostante il tutto il nome deve essee univoco
                $tcm->Options->pushErrorMessage('You have entered a name that already exists. IDs are NOT case-sensitive');
            }
        }
        if ($snippet['code'] == '') {
            $tcm->Options->pushErrorMessage('Paste your HTML Tracking Code into the textarea');
        }

        if (!$tcm->Options->hasErrorMessages()) {
            $snippet = $tcm->Manager->put($snippet['id'], $snippet);
            if ($id <= 0) {
                $tcm->Options->pushSuccessMessage('Editor.Add', $snippet['id'], $snippet['name']);
                $snippet = $tcm->Manager->get('', TRUE);
            } else {
                $tcm->Utils->redirect(TCM_PAGE_MANAGER.'&id='.$id);
                exit();
            }
        }
    }
    $tcm->Options->writeMessages();

    if($tcm->Manager->rc()<=0 && $id<=0) {
        $tcm->Utils->redirect(TCM_PAGE_MANAGER);
        exit();
    }
    tcm_ui_free_notice();

    ?>
    <script>
        jQuery(function(){
            var tcmPostTypes=[];

            <?php
            $types=$tcm->Utils->query(TCM_QUERY_POST_TYPES);
            foreach($types as $v) { ?>
                tcmPostTypes.push('<?php echo $v['name']?>');
            <?php } ?>

            //enable/disable some part of except creating coherence
            function tcmCheckVisible() {
                var showExceptCategories=true;
                var showExceptTags=true;
                var showExceptPostTypes={};
                jQuery.each(tcmPostTypes, function (i,v) {
                    showExceptPostTypes[v]=true;
                });

                var $all=jQuery('#includeEverywhereActive');
                if(!$all.is(':checked')) {
                    showExceptCategories=false;
                    showExceptTags=false;

                    jQuery.each(tcmPostTypes, function (i,v) {
                        isCheck=jQuery('#includePostsOfType_'+v+'_Active').is(':checked');
                        selection=jQuery('#includePostsOfType_'+v).select2("val");
                        found=false;
                        for(i=0; i<selection.length; i++) {
                            if(parseInt(selection[i])==-1){
                                found=true;
                            }
                        }

                        showExceptPostTypes[v]=false;
                        if(isCheck && found) {
                            showExceptPostTypes[v]=true;
                            if(v!='page') {
                                showExceptCategories=true;
                                showExceptTags=true;
                            }
                        }
                    });
                }

                //hide/show except post type if all the website is selected
                //or [All] is selected in a specific post type select
                var showExcept=false;
                jQuery.each(showExceptPostTypes, function (k,v) {
                    if(v) {
                        //at least one post type to show except
                        showExcept=true;
                    }
                    tcmShowHide('#exceptPostsOfType_'+k+'Box', v);
                });

                tcmShowHide('#exceptCategoriesBox', showExceptCategories);
                tcmShowHide('#exceptTagsBox', showExceptTags);

                showExcept=(showExcept || showExceptTags || showExceptCategories);
                tcmShowHide('#tcm-except-div', showExcept);
            }
            function tcmShowHide(selector, show) {
                $selector=jQuery(selector);
                if(show) {
                    $selector.show();
                } else {
                    $selector.hide();
                }
            }

            /*jQuery(".tcmTags").select2({
                placeholder: "Type here..."
                , theme: "classic"
            }).on('change', function() {
                tcmCheckVisible();
            });*/
            jQuery(".tcmLineTags").select2({
                placeholder: "Type here..."
                , theme: "classic"
                , width: '550px'
            });

            jQuery('.tcm-hideShow').click(function() {
                tcmCheckVisible();
            });
            jQuery('.tcm-hideShow, input[type=checkbox]').change(function() {
                tcmCheckVisible();
            });
            jQuery('.tcmLineTags').on('change', function() {
                tcmCheckVisible();
            });
            tcmCheckVisible();
        });
    </script>
    <?php

    $tcm->Form->formStarts();
    $tcm->Form->hidden('id', $snippet);
    $tcm->Form->checkbox('active', $snippet);
    $tcm->Form->text('name', $snippet);
    $tcm->Form->textarea('code', $snippet);
    $values = array(TCM_POSITION_HEAD, TCM_POSITION_BODY, TCM_POSITION_FOOTER);
    $tcm->Form->select('position', $snippet, $values, FALSE);

    $tcm->Form->p('When do you want to add this code?');
    $args=array('class'=>'tcm-hideShow tcm-checkbox'
        , 'tcm-hideIfTrue'=>'true'
        , 'tcm-hideShow'=>'tcm-include-div');
    $tcm->Form->checkbox('includeEverywhereActive', $snippet, 1, $args);

    $args=array('id'=>'tcm-include-div', 'name'=>'tcm-include-div', 'style'=>'margin-top:10px;');
    $tcm->Form->divStarts($args);
    tcm_formOptions('include', $snippet);
    $tcm->Form->divEnds();

    $args=array('id'=>'tcm-except-div', 'name'=>'tcm-except-div');
    $tcm->Form->divStarts($args);
    $tcm->Form->p('Exclude when?');
    tcm_formOptions('except', $snippet);
    $tcm->Form->divEnds();

    $tcm->Form->nonce('tcm_nonce', 'tcm_nonce');
    tcm_notice_pro_features();
    $tcm->Form->submit('Save');
    if($id>0) {
        $tcm->Form->delete($id);
    }
    $tcm->Form->formEnds();
}

function tcm_notice_pro_features() {
    global $tcm;

    ?>
    <br/>
    <div class="message updated below-h2">
        <div style="height:10px;"></div>
        <?php
        $i=1;
        while($tcm->Lang->H('Notice.ProHeader'.$i)) {
            $tcm->Lang->P('Notice.ProHeader'.$i);
            echo '<br/>';
            ++$i;
        }
        $i=1;
        ?>
        <br/>
        <?php

        $options = array('public' => TRUE, '_builtin' => FALSE);
        $q=get_post_types($options, 'names');
        if(is_array($q) && count($q)>0) {
            sort($q);
            $q=implode(', ', $q);
            $q='(<b>'.$q.'</b>)';
        } else {
            $q='';
        }

        while($tcm->Lang->H('Notice.ProFeature'.$i)) { ?>
            <div style="clear:both; margin-top: 2px;"></div>
            <div style="float:left; vertical-align:middle; height:24px; margin-right:5px;">
                <img src="<?php echo TCM_PLUGIN_IMAGES?>tick.png" />
            </div>
            <div style="float:left; vertical-align:middle; height:24px;">
                <?php $tcm->Lang->P('Notice.ProFeature'.$i, $q)?>
            </div>
            <?php ++$i;
        }
        ?>
        <div style="clear:both;"></div>
        <div style="height:10px;"></div>
    </div>
    <br/>
<?php }

function tcm_formOptions($prefix, $snippet) {
    global $tcm;

    $types=$tcm->Utils->query(TCM_QUERY_POST_TYPES);
    foreach($types as $v) {
        $args = array('post_type' => $v['name'], 'all' => TRUE);
        $values = $tcm->Utils->query(TCM_QUERY_POSTS_OF_TYPE, $args);

        $keyActive=$prefix.'PostsOfType_'.$v['name'].'_Active';
        $keyArray=$prefix.'PostsOfType_'.$v['name'];
        if($snippet[$keyActive]==0 && count($snippet[$keyArray])==0) {
            //when enabled default selected -1
            $snippet[$keyArray]=array(-1);
        }
        $tcm->Form->checkSelect($keyActive, $keyArray, $snippet, $values);
    }
}
