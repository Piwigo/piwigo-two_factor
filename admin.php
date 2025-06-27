<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $page, $conf;

$page['tab'] = 'config';

// Create tabsheet
include_once(PHPWG_ROOT_PATH . 'admin/include/tabsheet.class.php');
$tabsheet = new tabsheet();
$tabsheet->set_id('two_factor_tab');
$tabsheet->add('config', '<span class="icon-cog"></span>'.l10n('Configuration'), TF_ADMIN . '-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();

// test
require_once(TF_PATH . '/class/totp.class.php');
require_once(TF_PATH . '/class/twofactor.class.php');
$secret = PwgTOTP::generateSecret();
$generate_code = PwgTOTP::generateCode($secret);
$generate_code_brut = PwgTOTP::generateCode('5OO66IIAA6NYF2FZYYSVSYHW4VTYOSBJ');

 $template->assign(array(
  'secret1' => $secret,
  'code1' => $generate_code,
  'url1' => PwgTOTP::getOtpAuthUrl($secret),
  'secret2' => '5OO66IIAA6NYF2FZYYSVSYHW4VTYOSBJ',
  'code2' => $generate_code_brut,
  'url2' => PwgTOTP::getOtpAuthUrl('5OO66IIAA6NYF2FZYYSVSYHW4VTYOSBJ'),
  'qrcode' => PwgTOTP::getQrCode('5OO66IIAA6NYF2FZYYSVSYHW4VTYOSBJ'),
  'url' => get_absolute_root_url(),
  // 'isEnabled' => new PwgTwoFactor()->isEnabled(),
 ));
//  global $user;
//  echo '<pre>';
//  print_r($user);
//  //print_r(getuserdata(23232, false));
//  echo '</pre>';
//end test

$posted = 'non';
if (isset($_POST['TEST_MAIL_TOTP']))
{
  $totp = new PwgTwoFactor('email')->setup();
  $posted = 'oui';
}
$template->assign(array('posted' => $posted));

$template->set_filename('two_factor_plugin_content', TF_REALPATH . '/admin/template/configuration.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'two_factor_plugin_content');