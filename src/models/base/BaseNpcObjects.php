<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
abstract class BaseNpcObjects extends Doctrine_Record
{

  public function setTableDefinition()
  {
    $this->setTableName('npc_objects');
    $this->hasColumn('object_id', 'integer', 4, array (
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
    $this->hasColumn('objecttype_id', 'integer', 2, array (
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
    $this->hasColumn('name1', 'string', 128, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(128)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('name2', 'string', 128, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(128)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'notnull' => false,
  'autoincrement' => false,
));
    $this->hasColumn('is_active', 'integer', 2, array (
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
  }


}