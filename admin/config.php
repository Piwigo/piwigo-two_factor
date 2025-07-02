<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $template, $conf;

$template->assign(array(
  'TF_CONFIG' => $conf['two_factor'],
  'PWG_TOKEN' => get_pwg_token(),
));

$template->block_footer_script(null, 'const TF_CONFIG = '.json_encode($conf['two_factor']).';');