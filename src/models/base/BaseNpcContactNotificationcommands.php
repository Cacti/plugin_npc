<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
abstract class BaseNpcContactNotificationcommands extends Doctrine_Record
{

  public function setTableDefinition()
  {
    $this->setTableName('npc_contact_notificationcommands');
    $this->hasColumn('contact_notificationcommand_id', 'integer', 4, array (
  'alltypes' => 
  array (
    0 => 'integer',
  ),
  'ntype' => 'int(11)',
  'unsigned' => 0,
  'values' => 
  array (
  ),
  'primary' => true,
  'notnull' => true,
  'autoincrement' => true,
));
    $this->hasColumn('instance_id', 'integer', 2, array (
  'alltypes' => 
  array (
    0 => 'integer',
  ),
  'ntype' => 'smallint(6)',
  'unsigned' => 0,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('contact_id', 'integer', 4, array (
  'alltypes' => 
  array (
    0 => 'integer',
  ),
  'ntype' => 'int(11)',
  'unsigned' => 0,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('notification_type', 'integer', 2, array (
  'alltypes' => 
  array (
    0 => 'integer',
  ),
  'ntype' => 'smallint(6)',
  'unsigned' => 0,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('command_object_id', 'integer', 4, array (
  'alltypes' => 
  array (
    0 => 'integer',
  ),
  'ntype' => 'int(11)',
  'unsigned' => 0,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('command_args', 'string', 255, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(255)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
  }


}