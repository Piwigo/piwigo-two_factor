<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

require_once(TF_REALPATH . '/class/totp.class.php');


class PwgTwoFactor
{
  public static $allowed_methods = array('external_app', 'email');
  public $method;
  
  private $user;
  private $secret = null;

  function __construct($method)
  {
    if (!$this->isAllowedMethod($method)) throw new Error('Wrong two factor method');

    global $user;
    $this->user = $user;
    $this->method = $method;
    $this->secret = $this->getStoredSecret();
  }

  /**
   * Check if the given method is allowed
   *
   * @return bool
   */
  private static function isAllowedMethod($method)
  {
    return in_array($method, self::$allowed_methods, true);
  }

  /**
   * Get stored secret
   *
   * @return string|null
   */
  private function getStoredSecret($user_id = null)
  {
    $user_id = $user_id ?? pwg_db_real_escape_string($this->user['id']);
    $query = '
SELECT secret
  FROM '.TF_TABLE.'
WHERE user_id = '.$user_id.'
  AND method = \''.$this->method.'\'
;';
    $result = pwg_db_fetch_assoc(pwg_query($query));
    if ($result && isset($result['secret']))
    {
      return $result['secret'];
    }
    return null;
  }

  /**
   * Check if two factor is enabled
   *
   * @param int $user_id
   * @param string $method method of authenticator (mail, external app, piwigo app)
   * @return bool
   */
  public static function isEnabled($user_id, $method = null)
  {
    $query = '
SELECT COUNT(*)
  FROM ' . TF_TABLE . '
WHERE user_id = ' . pwg_db_real_escape_string($user_id) . '
';
    if ($method && self::isAllowedMethod($method)) {
      $query .= ' AND method = \'' . pwg_db_real_escape_string($method) . '\'';
    }
    $query .= ';';

    list($count) = pwg_db_fetch_row(pwg_query($query));

    if ($count > 0) {
      return true;
    }
    return false;
  }

  /**
   * Check if two factor is activated
   *
   * @param string $method method of authenticator (mail, external app, piwigo app)
   * @return bool|null
   */
  public static function isActivated($method = null)
  {
    if ($method && !self::isAllowedMethod($method))
    {
      return null;
    }

    global $conf;

    if (!$method)
    {
      return $conf['two_factor']['external_app']['enabled'] || $conf['two_factor']['email']['enabled'];
    }

    return $conf['two_factor'][$method]['enabled'];
  }

  /**
   * Get the lockout duration for the current user
   *
   * @return array|null lockout duration date and diff, null if empty
   * 
   */
  public function getLockoutDuration()
  {
    $query = '
SELECT
  tf_lockout_duration,
  NOW(),
  NOW() > tf_lockout_duration
  FROM `'.USER_INFOS_TABLE.'`
  WHERE user_id = '.$this->user['id'].'
;';

    list($lockout_duration, $now, $can_login) = pwg_db_fetch_row(pwg_query($query));

    // no lockout or lockout expired
    if (empty($lockout_duration) || $can_login === null || $can_login == 1)
    {
        return null;
    }
    
    $expired_on = str2DateTime($lockout_duration);
    $now = str2DateTime($now);
    $diff = dateDiff($now, $expired_on);

    return array(
      'lockout_duration' => $lockout_duration,
      'expires_in' => $diff
    );
  }

  /**
   * Set the lockout duration for the current user
   *
   * @return array lockout duration date and diff
   * 
   */
  public function setLockoutDuration()
  {
    global $conf;
    $lockout_duration = $conf['two_factor']['general']['lockout_duration'];
    list($db_future) = pwg_db_fetch_row(pwg_query('SELECT ADDDATE(NOW(), INTERVAL '.$lockout_duration.' SECOND);'));
    single_update(
      USER_INFOS_TABLE,
      array('tf_lockout_duration' => $db_future),
      array('user_id' => $this->user['id'])
    );
    return $this->getLockoutDuration();
  }

  /**
   * Clear the lockout duration
   * 
   * @param string|null $user_id if null we use the current id user
   *
   * @return bool
   * 
   */
  public function clearLockoutDuration($user_id = null)
  {
    $user_id = $user_id ?? $this->user['id'];
    single_update(
      USER_INFOS_TABLE,
      array('tf_lockout_duration' => null),
      array('user_id' => pwg_db_real_escape_string($user_id))
    );

    return true;
  }

  /**
   * Setup two factor authenticator
   *
   * @return array|bool tmp_secret and qrcode, false is something wrong
   * 
   * The secret is also stored in $_SESSION 
   */
  public function setup()
  {
    global $conf;

    $this->secret = PwgTOTP::generateSecret();
    $setup = array(
      'tmp_secret' => $this->secret
    );

    switch ($this->method)
    {
      case 'email':
        if (!$this->user['email']) return null;
        include_once(PHPWG_ROOT_PATH.'include/functions_mail.inc.php');

        $code = PwgTOTP::generateCode($this->secret);
        $message = tf_generate_mail_template($this->user['username'], $code, true);  

        $send_email = pwg_mail(
          $this->user['email'],
          array(
            'subject' => '['.$conf['gallery_title'].'] '.l10n('Two Factor configuration'),
            'content' => $message,
            'content_format' => 'text/html',
          )
        );
        if (!$send_email) {
          $setup = false;
          break;
        }

        pwg_set_session_var(TF_SESSION_MAIL_CODE, $code);
        pwg_set_session_var(TF_SESSION_MAIL_SENT_AT, time());

        $setup = true;
        break;

      case 'external_app':
        $setup['qrcode'] = PwgTOTP::getQrCode($this->secret);
        $setup['recovery_codes'] = array();
        $recovery_codes_hash = array();
        for ($i = 0; $i < 8; $i++)
        {
          $code = strtoupper(bin2hex(random_bytes(4)));
          $setup['recovery_codes'][] = $code;
          $recovery_codes_hash[] = pwg_password_hash($code);
        }
        pwg_set_session_var(TF_SESSION_TMP_RECOVERY_CODES, json_encode($recovery_codes_hash));
        break;
      
      default:
        return null;
    }

    pwg_set_session_var(TF_SESSION_TMP_SECRET_PREFIX . $this->method, $this->secret);

    return $setup;
  }

  /**
   * Finalise setup two factor authenticator
   *
   * @param string $code Digits 6 TOTP Code
   * @return bool
   * 
   * The secret is also deleted in $_SESSION 
   */
  public function finaliseSetup($code)
  {
    if (!pwg_get_session_var(TF_SESSION_TMP_SECRET_PREFIX . $this->method)) return false;
  
    $this->secret = pwg_get_session_var(TF_SESSION_TMP_SECRET_PREFIX . $this->method);
    if ($this->verifyCode($code))
    {
      $this->saveSecret();
      pwg_unset_session_var(TF_SESSION_TMP_SECRET_PREFIX . $this->method);
      pwg_unset_session_var(TF_SESSION_TMP_RECOVERY_CODES);
      pwg_unset_session_var(TF_SESSION_MAIL_CODE);
      pwg_unset_session_var(TF_SESSION_MAIL_SENT_AT);
      return true;
    }
    return false;
  }

  /**
   * Save the two factor secret to the database for the current user and method.
   *
   * @return false|void Returns false on failure, void on success.
   */
  public function saveSecret() 
  {
    if (!$this->secret) return false;

    $curr_secret = pwg_db_real_escape_string($this->secret);
    $user_id = pwg_db_real_escape_string($this->user['id']);
    $method = pwg_db_real_escape_string($this->method);

    $codes = pwg_get_session_var(TF_SESSION_TMP_RECOVERY_CODES);
    $recovery_codes_sql = "NULL";
    if ($codes)
    {
      $recovery_codes_sql = "'" . pwg_db_real_escape_string($codes) . "'";
    }

    $query = '
INSERT INTO '.TF_TABLE.' (user_id, secret, method, recovery_codes, enabled_at)
  VALUES('.$user_id.', \''.$curr_secret.'\', \''.$method.'\', '.$recovery_codes_sql.', NOW())
  ON DUPLICATE KEY UPDATE 
    secret = \''.$curr_secret.'\',
    recovery_codes = '.$recovery_codes_sql.'
';
    pwg_query($query);
  }

  /**
   * Delete stored secret
   *
   * @return bool
   */
  public function deleteSecret($user_id = null)
  {
    if ($this->getStoredSecret($user_id))
    {
      $user_id = $user_id ?? pwg_db_real_escape_string($this->user['id']);
      $method = pwg_db_real_escape_string($this->method);
      $query = '
DELETE FROM '.TF_TABLE.'
  WHERE 
    user_id = '.$user_id.'
    AND method = \''.$method.'\'
;';
      pwg_query($query);
      return true;
    }
    return false;
  }

  /**
   * Verify Totp code
   *
   * @param string $code Digits 6 TOTP Code
   * @param string $secret Base32-encoded secret
   * @return bool
   */
  public function verifyCode($code, $secret = null)
  {
    if ('email' === $this->method)
    {
      $session_code = pwg_get_session_var(TF_SESSION_MAIL_CODE);
      $session_sent_at = pwg_get_session_var(TF_SESSION_MAIL_SENT_AT);
      return $code === $session_code && (time() - $session_sent_at < 600);
    }
    return PwgTOTP::verifyCode($code, $secret ?? $this->secret);
  }

  /**
   * Generate Totp code
   *
   * @return string 6 digits TOTP code
   */
  public function generateCode()
  {
    $code = PwgTOTP::generateCode($this->secret);
    if ('email' === $this->method)
    {
      pwg_set_session_var(TF_SESSION_MAIL_CODE, $code);
      pwg_set_session_var(TF_SESSION_MAIL_SENT_AT, time());
    }
    return $code;
  }

  /**
   * Get Hashed Recovery Code
   *
   * @return string[]
   */
  public function getRecoveryCodes()
  {
    if ('email' === $this->method) return array();
    $query = '
SELECT *
  FROM `'.TF_TABLE.'`
  WHERE user_id = '.$this->user['id'].'
  AND method = \'external_app\' 
    ';

    $result = pwg_db_fetch_assoc(pwg_query($query));
    if ($result && isset($result['recovery_codes']))
    {
      return json_decode($result['recovery_codes']);
    }
    return array();
  }

  /**
   * Verify Recovery Code
   *
   * @param string $code Recovery Code
   * 
   * @return bool
   */
  public function verifyRecoveryCodes($code)
  {
    $codes = $this->getRecoveryCodes();
    if (0 === count($codes))
    {
      return false;
    }

    for ($i = 0; $i < count($codes); $i++)
    {
      $verify = pwg_password_verify($code, $codes[$i]);
      if ($verify)
      {
        unset($codes[$i]);
        $codes = count($codes) > 0 ? json_encode(array_values($codes)) : null;
        single_update(
          TF_TABLE,
          array('recovery_codes' => $codes),
          array(
            'user_id' => $this->user['id'],
            'method' => 'external_app'
          )
        );
        return true;
      }
    }
    return false;
  }
}
