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
 * `Two Factor` : Force logout
 */
function tf_force_logout($lockout_duration = null) {
  tf_clean_login();
  logout_user();
  if (isset($lockout_duration['expires_in']))
  {
    $wait = '0s';
    if ($lockout_duration['expires_in']->i > 0)
    {
      $wait = $lockout_duration['expires_in']->i . '-m';
    }
    else
    {
      $wait = $lockout_duration['expires_in']->s . '-s';
    }
    redirect(get_root_url().'identification.php?tf_lockout='.$wait);
  }
  else
  {
    redirect(get_root_url().'identification.php?tf_login_error');
  }
  exit;
}

/**
 * `Two Factor` : Login and redirect to home
 */
function tf_login_and_redirect()
{
  global $user;
  if ($user['tf_lockout_duration'])
  {
    single_update(
      USER_INFOS_TABLE,
      array('tf_lockout_duration' => null),
      array('user_id' => $user['id'])
    );
  }
  tf_clean_login();
  redirect(get_gallery_home_url());
  exit;
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

/**
 * `Two Factor` : get default conf
 */
function tf_get_default_conf()
{
  return array(
    'external_app' => array(
      'enabled' => true,                      // Enable 2FA by external app
      'totp_window' => 1,                     // TOTP tolerance window (±30 seconds)
      'code_lifetime' => 30,                  // TOTP code lifetime in seconds (30 = 30 seconds)
    ),
    'email' => array(
      'enabled' => false,                     // Enable 2FA by email
      'totp_window' => 1,                     // TOTP tolerance window (±30 seconds)
      'code_lifetime' => 900,                 // Email code lifetime in seconds (900 = 15 minutes)
      'setup_delay' => 60,                    // Delay between setup email sends in seconds
      'verify_delay' => 30                    // Delay between verification email sends in seconds
    ),
    'general' => array(
      'max_attempts' => 3,                    // Maximum number of failed attempts before lockout
      'lockout_duration' => 300,              // Lockout duration in seconds after max attempts (300 = 5 minutes)
      // 'auto_enable_existing_users' => false,  // later
      // 'auto_enable_new_users' => false,       // later
    )
  );
}