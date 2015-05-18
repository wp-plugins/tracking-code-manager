<?php
function tcm_ui_faq() {
    global $tcm;
    $i=1;
    while($tcm->Lang->H('Faq.Question'.$i)) {
        $q=$tcm->Lang->L('Faq.Question'.$i);
        $r=$tcm->Lang->L('Faq.Response'.$i);
        ?>
        <p>
            <b><?php echo $q?></b>
            <br/>
            <?php echo $r?>
        </p>
        <?php
        ++$i;
    }
}