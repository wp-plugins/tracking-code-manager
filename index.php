<?php
/*
Plugin Name: Tracking Code Manager
Plugin URI: http://intellywp.com/tracking-code-manager/
Description: A plugin to manage ALL your tracking code and conversion pixels, simply. Compatible with Facebook Ads, Google Adwords, WooCommerce, Easy Digital Downloads, WP eCommerce.
Author: IntellyWP
Author URI: http://intellywp.com/
Email: aleste@intellywp.com
Version: 1.6.5
*/
define('TCM_PLUGIN_PREFIX', 'TCM_');
define('TCM_PLUGIN_FILE',__FILE__);
define('TCM_PLUGIN_NAME', 'tracking-code-manager');
define('TCM_PLUGIN_VERSION', '1.6.5');
define('TCM_PLUGIN_AUTHOR', 'IntellyWP');
define('TCM_PLUGIN_ROOT', dirname(__FILE__).'/');
define('TCM_PLUGIN_IMAGES', plugins_url( 'assets/images/', __FILE__ ));
define('TCM_PLUGIN_ASSETS', plugins_url( 'assets/', __FILE__ ));

define('TCM_LOGGER', FALSE);

define('TCM_QUERY_POSTS_OF_TYPE', 1);
define('TCM_QUERY_POST_TYPES', 2);
define('TCM_QUERY_CATEGORIES', 3);
define('TCM_QUERY_TAGS', 4);
define('TCM_QUERY_CONVERSION_PLUGINS', 5);

define('TCM_INTELLYWP_SITE', 'http://www.intellywp.com/');
define('TCM_INTELLYWP_RECEIVER', TCM_INTELLYWP_SITE.'wp-content/plugins/intellywp-manager/data.php');
define('TCM_PAGE_FAQ', TCM_INTELLYWP_SITE.'tracking-code-manager');
define('TCM_PAGE_PREMIUM', TCM_INTELLYWP_SITE.'tracking-code-manager');
define('TCM_PAGE_MANAGER', admin_url().'options-general.php?page='.TCM_PLUGIN_NAME);

define('TCM_POSITION_HEAD', 0);
define('TCM_POSITION_BODY', 1);
define('TCM_POSITION_FOOTER', 2);
define('TCM_POSITION_CONVERSION', 3);

define('TCM_TAB_EDITOR', 'editor');
define('TCM_TAB_EDITOR_URI', TCM_PAGE_MANAGER.'&tab='.TCM_TAB_EDITOR);
define('TCM_TAB_MANAGER', 'manager');
define('TCM_TAB_MANAGER_URI', TCM_PAGE_MANAGER.'&tab='.TCM_TAB_MANAGER);
define('TCM_TAB_SETTINGS', 'settings');
define('TCM_TAB_SETTINGS_URI', TCM_PAGE_MANAGER.'&tab='.TCM_TAB_SETTINGS);
define('TCM_TAB_FAQ', 'faq');
define('TCM_TAB_FAQ_URI', TCM_PAGE_MANAGER.'&tab='.TCM_TAB_FAQ);
define('TCM_TAB_ABOUT', 'about');
define('TCM_TAB_ABOUT_URI', TCM_PAGE_MANAGER.'&tab='.TCM_TAB_ABOUT);
define('TCM_TAB_WHATS_NEW', 'whatsnew');
define('TCM_TAB_WHATS_NEW_URI', TCM_PAGE_MANAGER.'&tab='.TCM_TAB_WHATS_NEW);

include_once(dirname(__FILE__).'/autoload.php');
tcm_include_php(dirname(__FILE__).'/includes/');

global $tcm;
$tcm=new TCM_Singleton();
$tcmTabs=new TCM_Tabs();

class TCM_Singleton {
    var $Lang;
    var $Utils;
    var $Form;
    var $Check;
    var $Options;
    var $Logger;
    var $Cron;
    var $Tracking;
    var $Manager;
    var $Ecommerce;
    var $Plugin;

    function __construct() {
        $this->Lang=new TCM_Language();
        $this->Lang->load('tcm', TCM_PLUGIN_ROOT.'languages/Lang.txt');

        $this->Utils=new TCM_Utils();
        $this->Form=new TCM_Form();
        $this->Check=new TCM_Check();
        $this->Options=new TCM_Options();
        $this->Logger=new TCM_Logger();
        $this->Cron=new TCM_Cron();
        $this->Tracking=new TCM_Tracking();
        $this->Manager=new TCM_Manager();
        $this->Ecommerce=new TCM_Ecommerce();
        $this->Plugin=new TCM_Plugin();
    }
}
//from Settings_API_Tabs_Demo_Plugin
class TCM_Tabs {
    private $tabs = array();

    function __construct() {
        global $tcm;
        if($tcm->Utils->isAdminUser()) {
            add_filter('plugin_action_links', array(&$this, 'pluginActions'), 10, 2);
            add_action('admin_menu', array(&$this, 'attachMenu'));
            add_action('admin_enqueue_scripts',  array(&$this, 'enqueueScripts'));
        }
    }

    function attachMenu() {
        $name='Tracking Code Manager';
        add_submenu_page('options-general.php'
            , $name, $name
            , 'edit_posts', TCM_PLUGIN_NAME, array(&$this, 'showTabPage'));
    }
    function pluginActions($links, $file) {
        global $tcm;
        if($file==TCM_PLUGIN_NAME.'/index.php'){
            $settings = "<a href='".TCM_PAGE_MANAGER."'>" . $tcm->Lang->L('Settings') . '</a> ';
            $url='http://intellywp.com/tracking-code-manager/?utm_source=free-users&utm_medium=tcm-plugins&utm_campaign=TCM';
            $premium = "<a href='".$url."'>" . $tcm->Lang->L('PREMIUM') . '</a> ';
            $links = array_merge(array($settings, $premium), $links);
        }
        return $links;
    }
    function enqueueScripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('suggest');
        wp_enqueue_script('jquery-ui-autocomplete');

        //to create a different name each version to bypass cache problem
        $p='?p='.TCM_PLUGIN_VERSION;
        wp_enqueue_style('tcm-css', plugins_url('assets/css/style.css', __FILE__ ).$p);
        wp_enqueue_style('tcm-select2-css', plugins_url('assets/deps/select2-3.5.2/select2.css', __FILE__ ).$p);
        wp_enqueue_script('tcm-select2-js', plugins_url('assets/deps/select2-3.5.2/select2.min.js', __FILE__ ).$p);
        wp_enqueue_script('tcm-starrr-js', plugins_url('assets/deps/starrr/starrr.js', __FILE__ ).$p);

        wp_register_script('tcm-autocomplete', plugins_url('assets/js/tcm-autocomplete.js', __FILE__ ).$p, array('jquery', 'jquery-ui-autocomplete'), '1.0', FALSE);
        wp_localize_script('tcm-autocomplete', 'TCMAutocomplete', array('url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_script('tcm-autocomplete');
    }

    function showTabPage() {
        global $tcm;

	    $defaultTab=TCM_TAB_MANAGER;
        if($tcm->Options->isShowWhatsNew()) {
            $tab=TCM_TAB_WHATS_NEW;
	        $defaultTab=$tab;
            $this->tabs[TCM_TAB_WHATS_NEW]=$tcm->Lang->L('What\'s New');
            //$this->tabs[TCM_TAB_MANAGER]=$tcm->Lang->L('Start using the plugin!');
        } else {
            if($tcm->Plugin->isActive(TCM_PLUGINS_TRACKING_CODE_MANAGER_PRO)) {
                $this->tabs[TCM_TAB_MANAGER]=$tcm->Lang->L('Manager');
                $tab=TCM_TAB_MANAGER;
                $defaultTab=$tab;
            } else {
                $id=intval($tcm->Utils->qs('id', 0));
                $tab=$tcm->Utils->qs('tab', $defaultTab);

                if($id>0 || $tcm->Manager->rc()>0) {
                    $this->tabs[TCM_TAB_EDITOR]=$tcm->Lang->L($id>0 && $tab==TCM_TAB_EDITOR ? 'Edit' : 'Add new');
                } elseif($tab==TCM_TAB_EDITOR) {
                    $tab=TCM_TAB_MANAGER;
                }
                $this->tabs[TCM_TAB_MANAGER]=$tcm->Lang->L('Manager');
                $this->tabs[TCM_TAB_SETTINGS]=$tcm->Lang->L('Settings');
                $this->tabs[TCM_TAB_FAQ]=$tcm->Lang->L('FAQ & Video');
                $this->tabs[TCM_TAB_ABOUT]=$tcm->Lang->L('About');
            }
        }

        ?>
        <div class="wrap" style="margin:5px;">
            <?php
            $this->showTabs($defaultTab);
            $header='';
            switch ($tab) {
                case TCM_TAB_EDITOR:
                    $header=($id>0 ? 'Edit' : 'Add');
                    break;
                case TCM_TAB_WHATS_NEW:
                    $header='';
                    break;
                case TCM_TAB_MANAGER:
                    $header='Manager';
                    break;
                case TCM_TAB_SETTINGS:
                    $header='Settings';
                    break;
                case TCM_TAB_FAQ:
                    $header='Faq';
                    break;
                case TCM_TAB_ABOUT:
                    $header='About';
                    break;
            }

            if($header!='' && $tcm->Lang->H($header.'Title')) { ?>
                <h2><?php $tcm->Lang->P($header . 'Title', TCM_PLUGIN_VERSION) ?></h2>
                <?php if ($tcm->Lang->H($header . 'Subtitle')) { ?>
                    <div><?php $tcm->Lang->P($header . 'Subtitle') ?></div>
                <?php } ?>
                <br/>
            <?php }

            tcm_ui_first_time();
            switch ($tab) {
                case TCM_TAB_WHATS_NEW:
                    tcm_ui_whats_new();
                    break;
                case TCM_TAB_EDITOR:
                    tcm_ui_editor();
                    break;
                case TCM_TAB_MANAGER:
                    tcm_ui_manager();
                    break;
                case TCM_TAB_SETTINGS:
                    tcm_ui_track();
                    tcm_ui_settings();
                    break;
                case TCM_TAB_FAQ:
                    tcm_ui_track();
                    tcm_ui_faq();
                    break;
                case TCM_TAB_ABOUT:
                    tcm_ui_about();
                    tcm_ui_feedback();
                    break;
            } ?>
        </div>
    <?php }

    function showTabs($defaultTab) {
        global $tcm;
        $tab=$tcm->Check->of('tab', $defaultTab);
        if($tcm->Options->isShowWhatsNew()) {
            $tab=TCM_TAB_WHATS_NEW;
        }

        ?>
        <h2 class="nav-tab-wrapper" style="float:left; width:97%;">
            <?php
            foreach ($this->tabs as $k=>$v) {
                $active=($tab==$k ? 'nav-tab-active' : '');
                $style='';
                if($tcm->Options->isShowWhatsNew() && $k==TCM_TAB_MANAGER) {
                    $active='';
                    $style='background-color:#F2E49B';
                }
                ?>
                <a style="float:left; <?php echo $style?>" class="nav-tab <?php echo $active?>" href="?page=<?php echo TCM_PLUGIN_NAME?>&tab=<?php echo $k?>"><?php echo $v?></a>
                <?php
            }
            ?>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/css/font-awesome.min.css">
            <style>
                .starrr {display:inline-block}
                .starrr i{font-size:16px;padding:0 1px;cursor:pointer;color:#2ea2cc;}
            </style>
            <div style="float:right; display:none;" id="rate-box">
                <span style="font-weight:700; font-size:13px; color:#555;"><?php $tcm->Lang->P('Rate us')?></span>
                <div id="tcm-rate" class="starrr" data-connected-input="tcm-rate-rank"></div>
                <input type="hidden" id="tcm-rate-rank" name="tcm-rate-rank" value="5" />

            </div>
            <script>
                jQuery(function() {
                    jQuery(".starrr").starrr();
                    jQuery('#tcm-rate').on('starrr:change', function(e, value){
                        var url='https://wordpress.org/support/view/plugin-reviews/tracking-code-manager?rate=5#postform';
                        window.open(url);
                    });
                    jQuery('#rate-box').show();
                });
            </script>
        </h2>
        <div style="clear:both;"></div>
    <?php }
}
