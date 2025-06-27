<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * `Two Factor` : redirect to two factor authentication
 */
function tf_redirect()
{
  $redirect = get_root_url().'identification.php?tf';
  if ('identification.php' !== basename($_SERVER['SCRIPT_NAME']))
  {
    //redirect_html($redirect);
    redirect($redirect);
  }
}

/**
 * `Two Factor` : clean two factor session
 */
function tf_clean_login()
{
  unset(
    $_SESSION[TF_SESSION_VALIDATED],
    $_SESSION[TF_SESSION_TRIES_LEFT]
  );
}

/**
 * `Two Factor` : Mail rate limit per $_SESSION 
 */
function tf_mail_rate_limit($time, $session_key)
{
  if (!isset($_SESSION[$session_key]))
  {
    $_SESSION[$session_key] = time();
  }
  else
  {
    $time_diff = $time - $_SESSION[$session_key];
    if ($time_diff <= 30)
    {
      return 30 - $time_diff;
    }
    $_SESSION[$session_key] = time();
  }
  return true;
}

/**
 * `Two Factor` : get template mail
 */
function tf_generate_mail_template($username, $code, $setup = false)
{
  $message = '<p style="margin: 20px 0">';
  $message .= l10n('Hello %s,', $username).'</p>';
  if ($setup)
  {
    $message .= '<p style="margin: 20px 0">'.l10n('You are setting up two-factor authentication for your account.').'</p>';
  }
  $message .= '<p style="margin: 20px 0">'.l10n('Your verification code is: %s', $code).'</p>';
  $message .= '<p style="margin: 20px 0">'.l10n('This code will expire in a few minutes for security reasons.').'</p>';
  if ($setup)
  {
    $message .= '<p style="margin: 20px 0;">'.l10n('If you did not request this setup, please contact your administrator immediately.').'</p>'; 
  }
  
  return $message;
}
