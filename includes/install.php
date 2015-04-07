<?php

function tcm_install($networkwide=NULL) {
	global $wpdb;
}

register_activation_hook(TCM_PLUGIN_FILE, 'tcm_install');





