<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+
check_status(ACCESS_WEBMASTER);

global $page, $conf;

$page['tab'] = 'config';

// Create tabsheet
include_once(PHPWG_ROOT_PATH . 'admin/include/tabsheet.class.php');
$tabsheet = new tabsheet();
$tabsheet->set_id('two_factor_tab');
$tabsheet->add('config', '<span class="icon-cog"></span>'.l10n('Configuration'), TF_ADMIN . '-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();

$template->assign(array(
    'ADMIN_PAGE_TITLE' => l10n('Two Factor Authentication')
));

include_once(TF_REALPATH . '/admin/config.php');
// include_once(TF_REALPATH . 'admin/' . $page['tab'] . '.php');

$template->set_filename('two_factor_plugin_content', TF_REALPATH . '/admin/template/configuration.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'two_factor_plugin_content');