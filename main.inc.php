<?php
/*
Version: auto
Plugin Name: Two Factor
Plugin URI: auto
Author: Piwigo team
Author URI: https://github.com/Piwigo
Description: Two Factor Authenfication method.
Has Settings: true
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// check root directory
if (basename(dirname(__FILE__)) != 'two_factor')
{
  add_event_handler('init', 'tf_error');
  function tf_error()
  {
    global $page;
    $page['errors'][] = 'Two Factor folder name is incorrect, uninstall the plugin and rename it to "two_factor"';
  }
  return;
}

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+
global $prefixeTable;

define('TF_ID', basename(dirname(__FILE__)));
define('TF_PATH', PHPWG_PLUGINS_PATH . TF_ID . '/');
define('TF_TABLE', $prefixeTable . 'two_factor');
define('TF_REALPATH', realpath(TF_PATH));
define('TF_ADMIN', get_root_url() . 'admin.php?page=plugin-' . TF_ID);
define('TF_SESSION_TMP_USER_ID', 'tf_tmp_user_id');
define('TF_SESSION_TMP_SECRET_PREFIX', 'tf_tmp_secret_');
define('TF_SESSION_TMP_RECOVERY_CODES', 'tf_tmp_recovery_codes');
define('TF_SESSION_TRIES_LEFT', 'tf_tries_left');
define('TF_SESSION_VALIDATED', 'tf_tries_validated');
define('TF_SESSION_MAIL_SETUP_RATE_LIMIT', 'tf_mail_setup_rate_limit');
define('TF_SESSION_MAIL_VERIFY_RATE_LIMIT', 'tf_mail_verify_rate_limit');

// +-----------------------------------------------------------------------+
// | Init Two Factor                                                       |
// +-----------------------------------------------------------------------+

include_once(TF_REALPATH.'/includes/functions.inc.php');
$tf_events = TF_REALPATH.'/includes/events.inc.php';
$tf_fws = TF_REALPATH.'/includes/ws_functions.inc.php';

add_event_handler('init', 'tf_init');
add_event_handler('load_profile_in_template', 'tf_add_profile_block', EVENT_HANDLER_PRIORITY_NEUTRAL, $tf_events);
add_event_handler('ws_add_methods', 'tf_add_methods', EVENT_HANDLER_PRIORITY_NEUTRAL, $tf_fws);

add_event_handler('loc_begin_identification', 'tf_loc_begin_identification', EVENT_HANDLER_PRIORITY_NEUTRAL, $tf_events);
add_event_handler('loc_end_identification', 'tf_loc_end_identification', EVENT_HANDLER_PRIORITY_NEUTRAL, $tf_events);
add_event_handler('try_log_user', 'tf_try_log_user', PHP_INT_MAX, $tf_events);

function tf_init()
{
  global $user, $template, $page;
  // for debug
  // tf_clean_login();
  
  load_language('plugin.lang', TF_PATH);
  $template->assign(array(
    'TF_PATH' => TF_PATH,
  ));
  include_once(TF_REALPATH . '/class/twofactor.class.php');
  if (!is_a_guest() and isset($_SESSION[TF_SESSION_VALIDATED]) and true !== $_SESSION[TF_SESSION_VALIDATED])
  {
    /// authorize only one api method
    if (
      defined('IN_WS')
      and isset($_REQUEST['method'])
      and 'twofactor.sendEmail' === $_REQUEST['method']
    )
    {
      return;
    }

    // override user status to guest
    // and always redirect to identification.php?tf
    $user['status'] = 'guest';
    tf_redirect();
  }
}
