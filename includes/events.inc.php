<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * `Two Factor` : two_factor add profile block
 */
function tf_add_profile_block()
{
  global $template, $user;

  $block = array(
    'name' => l10n('Two Factor Authentication'),
    'desc' => l10n('Configure your two-factor authentication settings and secure your acount'),
    'template' => TF_REALPATH . '/template/tf_profile.tpl',
    'standard_show_save' => false
  );
  $template->append('PLUGINS_PROFILE', $block);

  $template->assign(array(
    'TF_STATUS_EXTERNAL_APP' => boolean_to_string(PwgTwoFactor::isEnabled($user['id'], 'external_app')),
    'TF_STATUS_EMAIL' => boolean_to_string(PwgTwoFactor::isEnabled($user['id'], 'email'))
  ));
}

/**
 * `Two Factor` : two_factor try log user
 */
function tf_try_log_user($success, $username, $password, $remember_me)
{
  global $user, $conf;

  if ($success and PwgTwoFactor::isEnabled($user['id']))
  {
    // for debug
    // echo '<pre>';
    // print_r(($success ? 'success' : 'not success'));
    // echo '</pre>';

    // the method passed in class is not important for this case
    $lockout_duration = new PwgTwoFactor('external_app')->getLockoutDuration();
    if ($lockout_duration)
    {
      tf_force_logout($lockout_duration);
    }

    // success is true so the user has entered a good combination of credentials
    // and PwgTwoFactor::isEnabled($user['id']) is the user has already set up two-factor authentication
    // now, redirect the user to the personalized login page with two-factor authentication.
    $_SESSION[TF_SESSION_VALIDATED] = false;
    $_SESSION[TF_SESSION_TRIES_LEFT] = $conf['two_factor']['general']['max_attempts'];
    tf_redirect();
  }
  
  return $success;
}

/**
 * `Two Factor` : two_factor loc begin identification
 */
function tf_loc_begin_identification()
{
  global $template, $page, $conf;

  if (isset($_GET['tf_login_error']))
  {
    $page['errors']['login_page_error'] = l10n('Invalid username or password!');
    return;
  }

  if (isset($_GET['tf_lockout']))
  {
    $message = l10n('Too many failed attempts. Please log in again.');
    $waiting = explode('-', $_GET['tf_lockout']);
    if (isset($waiting[1]) and 'm' === $waiting[1])
    {
      $message = l10n('Too many failed attempts. Please try again in %d minutes.', $waiting[0]);
    }
    else if (isset($waiting[1]) and 's' === $waiting[1])
    {
      $message = l10n('Too many failed attempts. Please try again in %d seconds.', $waiting[0]);
    }
    
    $page['errors']['login_page_error'] = $message;
    return;
  }

  if (isset($_POST['tf_verify_code']))
  {
    if (get_pwg_token() != $_POST['pwg_token'])
    {
      tf_force_logout();
    }

    $code = $_POST['tf_verify_code'];
    $method = $_POST['tf_method_code'];

    if (!in_array($method, PwgTwoFactor::$allowed_methods))
    {
      return $template->block_footer_script(null, 'window.toasterOnStart.push({text: "'.l10n('Trying to put wrong method...').'", icon: "error"})');
    }

    $template->block_footer_script(null, 'window.tfStartOn = "'.$method.'"');

    if (!preg_match('/^\d{6}$/', $code))
    {
      return $template->block_footer_script(null, 'window.toasterOnStart.push({text: "'.l10n('The code must be in the format: 000000').'", icon: "error"})');
    }

    $_SESSION[TF_SESSION_TRIES_LEFT] = $_SESSION[TF_SESSION_TRIES_LEFT] - 1;
    $tf = new PwgTwoFactor($method);
    $verify = $tf->verifyCode($code);
    if ($verify)
    {
      $tf->clearLockoutDuration();
      tf_login_and_redirect();
    }
    else
    {
      if ($_SESSION[TF_SESSION_TRIES_LEFT] <= 0)
      {
        $lockout_duration = $tf->setLockoutDuration();
        tf_force_logout($lockout_duration);
      }
      
      return $template->block_footer_script(null, 'window.toasterOnStart.push({text: "'.l10n('The code is invalid').'", icon: "error"})');
    }
  }

  if (isset($_POST['tf_recovery_codes']))
  {
    if (get_pwg_token() != $_POST['pwg_token'])
    {
      tf_force_logout();
    }

    $recovery_code = pwg_db_real_escape_string($_POST['tf_recovery_codes']);
    $tf_external = new PwgTwoFactor('external_app');
    $verify_code = $tf_external->verifyRecoveryCodes($recovery_code);

    $_SESSION[TF_SESSION_TRIES_LEFT] = $_SESSION[TF_SESSION_TRIES_LEFT] - 1;

    if ($verify_code)
    {
      $tf_external->clearLockoutDuration();
      tf_login_and_redirect();
    }
    else
    {
      if ($_SESSION[TF_SESSION_TRIES_LEFT] <= 0)
      {
        $lockout_duration = $tf_external->setLockoutDuration();
        tf_force_logout($lockout_duration);
      }
      return $template->block_footer_script(null, 'window.toasterOnStart.push({text: "'.l10n('Invalid recovery code').'", icon: "error"})');
    }
  }
}

/**
 * `Two Factor` : two_factor loc end identification
 */
function tf_loc_end_identification()
{
  global $template, $user, $page;
  if (isset($_GET['tf']) and (isset($_SESSION[TF_SESSION_VALIDATED]) and !$_SESSION[TF_SESSION_VALIDATED]))
  {
    $template->assign(array(
      'TF_STATUS_EXTERNAL_APP' => PwgTwoFactor::isEnabled($user['id'], 'external_app'),
      'TF_STATUS_EMAIL' => PwgTwoFactor::isEnabled($user['id'], 'email'),
      'F_ACTION' => 'identification.php?tf',
      'TF_LOGOUT' => get_root_url().'?act=logout',
      'PWG_TOKEN' => get_pwg_token()
    ));
    $template->set_filenames( array('identification'=> TF_REALPATH . '/template/tf_identification.tpl') );
  }
}

/**
 * `Two Factor` : two_factor ws users getList
 */
function tf_ws_users_getList($users)
{
  $user_ids = array();
  foreach ($users as $user_id => $user){
    $user_ids[] = $user_id;
  }
  if (count($user_ids) == 0){
    return $users;
  }

  // search tf_lockout_duration for each users
  return $users;
}