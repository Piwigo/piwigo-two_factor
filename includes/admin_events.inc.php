<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * add template for a tab in users modal
 */
function tf_add_tab_users_modal()
{
  global $page, $template, $conf, $user;

  if ('user_list' === $page['page'] && is_webmaster())
  {
    $template->set_filename('tf_user_list', TF_REALPATH.'/admin/template/tf_user_list.tpl');
    $template->assign(array(
      'TF_PATH' => TF_PATH,
    ));
    $template->parse('tf_user_list');
    $template->block_footer_script(null, 'const TF_CONFIG = '.json_encode($conf['two_factor']).';');
  }
  $template->block_footer_script(null, 'console.log('.json_encode($user).')');
}