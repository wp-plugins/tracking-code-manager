<?php
function tcm_ui_rate_us() {
    $rate_text = sprintf(__('Thank you for using <a href="%1$s" target="_blank">Easy Digital Downloads</a>! Please <a href="%2$s" target="_blank">rate us</a> on <a href="%2$s" target="_blank">WordPress.org</a>', 'edd'),
        'https://easydigitaldownloads.com',
        'https://wordpress.org/support/view/plugin-reviews/easy-digital-downloads?rate=5#postform'
    );
    return $rate_text;
}

function tcm_ui_about() {
    global $tcm;

    $tcm->Options->pushSuccessMessage($tcm->Lang->L('AboutNotice'));
    $tcm->Options->writeMessages();

    ?>
    <div><?php $tcm->Lang->P('AboutText')?></div>
    <style>
        ul li {
            padding:2px;
        }
    </style>
    <ul>
        <li>
            <img style="float:left; margin-right:10px;" src="<?php echo TCM_PLUGIN_IMAGES?>email.png" />
            <a href="mailto:aleste@intellywp.com">Email aleste@intellywp.com</a>
        </li>
        <li>
            <img style="float:left; margin-right:10px;" src="<?php echo TCM_PLUGIN_IMAGES?>twitter.png" />
            <a href="https://twitter.com/intellywp" target="_new">Twitter @intellywp</a>
        </li>
        <li>
            <img style="float:left; margin-right:10px;" src="<?php echo TCM_PLUGIN_IMAGES?>internet.png" />
            <a href="http://www.intellywp.com" target="_new">Website intellywp.com</a>
        </li>
    </ul>
    <?php
}