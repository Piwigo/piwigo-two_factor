<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * `Two Factor` : add new pwg method
 */
function tf_add_methods($arr)
{
  $service = &$arr[0];

  // Setup
  $service->addMethod(
    'twofactor.setup.email',
    'tf_setup_email',
    array(
      'email' => array(
        'flags' => WS_PARAM_OPTIONAL,
        'info' => 'To check the user email address'
      ),
      'code' => array(
        'flags' => WS_PARAM_OPTIONAL|WS_TYPE_POSITIVE,
        'info' => 'Totp code'
      ),
      'pwg_token' => array(),
    ),
    'Step 1: send only email / Step 2: send totp code',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => false,
    )
  );

  $service->addMethod(
    'twofactor.setup.externalApp',
    'tf_setup_external_app',
    array(
      'code' => array(
        'flags' => WS_PARAM_OPTIONAL|WS_TYPE_POSITIVE,
        'info' => 'Totp code'
      ),
      'pwg_token' => array(),
    ),
    '',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => false,
    )
  );

  // Others method
  $service->addMethod(
    'twofactor.status',
    'tf_status',
    array(),
    '',
    null,
    array(
      'hidden' => false,
      'post_only' => false,
      'admin_only' => false,
    )
  );

  $service->addMethod(
    'twofactor.setConfig',
    'tf_set_config',
    array(
      'config' => array(
        'flags' => WS_PARAM_FORCE_ARRAY,
        'info' => 'Must be an array',
      ),
      'pwg_token' => array(),
    ),
    '',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => true,
    )
  );

  $service->addMethod(
    'twofactor.sendEmail',
    'tf_send_email',
    array(
      'pwg_token' => array(),
    ),
    '',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => false,
    )
  );

  $service->addMethod(
    'twofactor.deactivate',
    'tf_deactivate',
    array(
      'two_factor_method' => array(
        'info' => 'Only email or external_app'
      ),
      'user_id' => array(
        'flags' => WS_PARAM_OPTIONAL,
        'info' => 'Only webmaster can deactivate 2FA for an another user'
      ),
      'pwg_token' => array(),
    ),
    '',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => false,
    )
  );
}

function tf_setup_generic($params, $method)
{
  global $logger, $user;

  // We can only set the 2FA if we are connected with pwg_ui
  // or not a guest
  if (is_a_guest() or !connected_with_pwg_ui())
  {
    return new PwgError(401, 'Access Denied');
  }

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  if (!preg_match('/(email|external_app)/', $method))
  {
    return new PwgError(401, 'Method can be only email or external_app');
  }

  $tf = new PwgTwoFactor($method);

  if (isset($params['code']))
  {
    $activated = $tf->finaliseSetup($params['code']);
    if ($activated) {
      $logger->info('[two_factor][user_id='.$user['id'].'][method='.$method.'][setup_step=finalized]');
      return true;
    }
    else
    {
      return false;
    }
  }

  $setup = $tf->setup();
  if (!$setup)
  {
    return new PwgError(401, 'Error during initialisation two factor for method:' . $method);
  }

  // logger
  $logger->info('[two_factor][user_id='.$user['id'].'][method='.$method.'][setup_step=initialized]');
  return $setup;
}

/**
 * `Two Factor` : Setup email
 */
function tf_setup_email($params)
{
  global $user;
  if (!$user['email'])
  {
    return new PwgError(401, 'Unable to activate 2FA by email');
  }

  if (isset($params['email']) and $user['email'] !== $params['email'])
  {
    return new PwgError(401, 'Unable to activate 2FA by email');
  }

  if (!isset($params['code']))
  {
    $limit_rate = tf_mail_rate_limit(time(), TF_SESSION_MAIL_SETUP_RATE_LIMIT);
    if (true !== $limit_rate)
    {
      return new PwgError(403, l10n('Please wait %s seconds before sending an email again.', $limit_rate));
    }
  }

  return tf_setup_generic($params, 'email');
}

/**
 * `Two Factor` : Setup external app
 */
function tf_setup_external_app($params)
{
  return tf_setup_generic($params, 'external_app');
}

/**
 * `Two Factor` : Get 2FA Status
 */
function tf_status()
{
  if (is_a_guest())
  {
    return new PwgError(401, 'Acess Denied');
  }

  global $user;
  return array(
    'external_app' => PwgTwoFactor::isEnabled($user['id'], 'external_app'),
    'email' => PwgTwoFactor::isEnabled($user['id'], 'email')
  );
}

/**
 * `Two Factor` : Set config
 */
function tf_set_config($params)
{
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  if (!connected_with_pwg_ui() or !is_webmaster())
  {
    return new PwgError(401, 'Access Denied');
  }

  if (
    !isset($params['config']['general'])
    or !isset($params['config']['external_app'])
    or !isset($params['config']['email'])
    )
  {
    return new PwgError(403, 'Missing parameter, must have: general, external_app, email');
  }

  $validated_conf = array();
  foreach ($params['config'] as $key => $config)
  {
    switch ($key)
    {
      case 'general':
        if (
          !isset($config['max_attempts']) 
          or !preg_match('/^\d+$/', $config['max_attempts'])
          or !isset($config['lockout_duration']) 
          or !preg_match('/^\d+$/', $config['lockout_duration'])
        ) {
          return new PwgError(403, 'Missing parameter general, must have: max_attempts, lockout_duration both as integer');
        }
        $validated_conf[$key]['max_attempts'] = intval($config['max_attempts']);
        $validated_conf[$key]['lockout_duration'] = intval($config['lockout_duration']);
        break;

      case 'external_app':
        if (
          !isset($config['enabled'])
          or !isset($config['totp_window'])
          or !preg_match('/^\d+$/', $config['totp_window'])
          or !isset($config['code_lifetime']) 
          or !preg_match('/^\d+$/', $config['code_lifetime'])
        ) {
          return new PwgError(403, 'Missing parameter external_app, must have: enabled as bool, totp_window, code_lifetime both as integer');
        }

        $validated_conf[$key]['totp_window'] = intval($config['totp_window']);
        $validated_conf[$key]['code_lifetime'] = intval($config['code_lifetime']);
        $validated_conf[$key]['enabled'] = get_boolean($config['enabled']);
        break;

      case 'email':
        if (
          !isset($config['enabled'])
          or !isset($config['totp_window'])
          or !preg_match('/^\d+$/', $config['totp_window'])
          or !isset($config['code_lifetime'])
          or !preg_match('/^\d+$/', $config['code_lifetime'])
          or !isset($config['setup_delay'])
          or !preg_match('/^\d+$/', $config['setup_delay'])
          or !isset($config['verify_delay'])
          or !preg_match('/^\d+$/', $config['verify_delay'])
        ) {
          return new PwgError(403, 'Missing parameter email, must have: enabled as bool, totp_window, code_lifetime, verify_delay, setup_delay y\'all as integer');
        }

        $validated_conf[$key]['totp_window'] = intval($config['totp_window']);
        $validated_conf[$key]['code_lifetime'] = intval($config['code_lifetime']);
        $validated_conf[$key]['setup_delay'] = intval($config['setup_delay']);
        $validated_conf[$key]['verify_delay'] = intval($config['verify_delay']);
        $validated_conf[$key]['enabled'] = get_boolean($config['enabled']);
        break;
    }
  }

  conf_update_param('two_factor', $validated_conf, true);
  $tf_config = safe_unserialize(conf_get_param('two_factor'));

  return array(
    'status' => 'success',
    'message' => 'The configuration has been successfully saved.',
    'configuration' => $tf_config,
  );
}

/**
 * `Two Factor` : Reset config
 */
function tf_reset_config()
{
  //
}

/**
 * `Two Factor` : Send Totp code by mail
 */
function tf_send_email($params)
{
  global $user, $conf;

  if (is_a_guest() or !connected_with_pwg_ui())
  {
    return new PwgError(401, 'Access Denied');
  }

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  $limit_rate = tf_mail_rate_limit(time(), TF_SESSION_MAIL_VERIFY_RATE_LIMIT);
  if (true !== $limit_rate)
  {
    return new PwgError(403, l10n('Please wait %s seconds before sending an email again.', $limit_rate));
  }

  if (!PwgTwoFactor::isEnabled($user['id'], 'email'))
  {
    return new PwgError(401, 'Email isn\'t initialized');
  }

  $generated_code = new PwgTwoFactor('email')->generateCode();
  include_once(PHPWG_ROOT_PATH . 'include/functions_mail.inc.php');

  $message = tf_generate_mail_template($user['username'], $generated_code, false);

  $send_email = @pwg_mail(
    $user['email'],
    array(
      'subject' => '[' . $conf['gallery_title'] . '] ' . l10n('Two Factor Authentication'),
      'content' => $message,
      'content_format' => 'text/html',
    )
  );

  return $send_email;
}

/**
 * `Two Factor` : Deactivate Two Factor
 */
function tf_deactivate($params)
{
  global $user, $logger;

  if (is_a_guest() or !connected_with_pwg_ui())
  {
    return new PwgError(401, 'Access Denied');
  }

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  if (!preg_match('/(email|external_app)/', $params['two_factor_method']))
  {
    return new PwgError(401, 'Method can be only email or external_app');
  }

  if (!is_webmaster() and isset($params['user_id']) and $user['id'] != $params['user_id'])
  {
    return new PwgError(401, 'Acess Denied');
  }

  $user_id = $params['user_id'] ?? $user['id'];

  if (PwgTwoFactor::isEnabled($user_id, $params['two_factor_method']))
  {
    new PwgTwoFactor($params['two_factor_method'])->deleteSecret($user_id);
    // logger
    $logger->info('[two_factor][user_id='.$user_id.'][method='.$params['two_factor_method'].'][action=deactivated]');
    return true;
  }

  return false;
}
