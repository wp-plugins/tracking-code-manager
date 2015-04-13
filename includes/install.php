<?php

function tcm_install($networkwide=NULL) {
	global $wpdb, $tcm;

    $time=$tcm->Options->getPluginInstallDate();
    if($time==0) {
        $tcm->Options->setPluginInstallDate(time());
    }
    $tcm->Options->setPluginUpdateDate(time());
}

register_activation_hook(TCM_PLUGIN_FILE, 'tcm_install');





