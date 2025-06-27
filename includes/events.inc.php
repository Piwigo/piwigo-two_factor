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
  global $user;

  if ($success and PwgTwoFactor::isEnabled($user['id']))
  {
    // for debug
    // echo '<pre>';
    // print_r(($success ? 'success' : 'not success'));
    // echo '</pre>';

    // success is true so the user has entered a good combination of credentials
    // and PwgTwoFactor::isEnabled($user['id']) is the user has already set up two-factor authentication
    // now, redirect the user to the personalized login page with two-factor authentication.
    $_SESSION[TF_SESSION_VALIDATED] = false;
    $_SESSION[TF_SESSION_TRIES_LEFT] = 3;
    tf_redirect();
  }
  
  return $success;
}

/**
 * `Two Factor` : two_factor loc begin identification
 */
function tf_loc_begin_identification()
{
  global $template, $page;

  if (isset($_GET['tf_login_error']))
  {
    $page['errors']['login_page_error'] = l10n('Too many failed attempts. Please log in again.');
    return;
  }

  if (isset($_POST['tf_verify_code']))
  {
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
    $verify = new PwgTwoFactor($method)->verifyCode($code);
    if ($verify)
    {
      tf_clean_login();
      redirect(get_gallery_home_url());
    }
    else
    {
      if ($_SESSION[TF_SESSION_TRIES_LEFT] <= 0)
      {
        tf_clean_login();
        logout_user();
        redirect(get_root_url().'identification.php?tf_login_error');
        exit;
      }
      
      return $template->block_footer_script(null, 'window.toasterOnStart.push({text: "'.l10n('The code is invalid').'", icon: "error"})');
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
      'PWG_TOKEN' => get_pwg_token()
    ));
    $template->set_filenames( array('identification'=> TF_REALPATH . '/template/tf_identification.tpl') );
  }
}