<?php
//per agganciarsi ogni volta che viene scritto un contenuto
add_filter('wp_head', 'tcm_head');
function tcm_head(){
    global $post, $tcm;

    $tcm->Options->setPostShown(NULL);
    if(is_page($post->ID) || is_single($post->ID)) {
        $tcm->Options->setPostShown($post);
        $tcm->Logger->info('POST ID=%s IS SHOWN', $post->ID);
    }

    //future development
    //is_archive();
    //is_post_type_archive();
    //is_post_type_hierarchical();
    //is_attachment();
    $tcm->Manager->writeCodes(TCM_POSITION_HEAD);
}
add_action('wp_footer', 'tcm_footer');
function tcm_footer() {
    global $tcm;
    //there isn't a hook when <BODY> starts
    $tcm->Manager->writeCodes(TCM_POSITION_BODY);
    $tcm->Manager->writeCodes(TCM_POSITION_FOOTER);
}