<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class two_factor_maintain extends PluginMaintain
{
  private $table;

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);

    global $prefixeTable;
    $this->table = $prefixeTable . 'two_factor';
  }

  /**
   * Plugin install
   */
  function install($plugin_version, &$errors = array())
  {
    global $conf;

    pwg_query('
CREATE TABLE IF NOT EXISTS `'. $this->table .'` (
  `user_id` mediumint(8) unsigned NOT NULL,
  `secret` VARCHAR(255) DEFAULT NULL,
  `method` VARCHAR(50) NOT NULL,
  `recovery_codes` TEXT DEFAULT NULL,
  `enabled_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`user_id`, `method`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;');

    if (empty($conf['two_factor']))
    {
      conf_update_param('two_factor', tf_get_default_conf(), true);
    }
  }

  /**
   * Plugin activate
   */
  function activate($plugin_version, &$errors = array())
  {
  }

  /**
   * Plugin deactivate
   */
  function deactivate()
  {
  }

  /**
   * Plugin update
   */
  function update($old_version, $new_version, &$errors = array())
  {
    $this->install($new_version, $errors);
  }

  /**
   * Plugin uninstallation
   */
  function uninstall()
  {
    pwg_query('DROP TABLE `'. $this->table .'`;');
    conf_delete_param('two_factor');
  }

}
