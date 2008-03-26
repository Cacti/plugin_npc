<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
abstract class BaseNpcConninfo extends Doctrine_Record
{

  public function setTableDefinition()
  {
    $this->setTableName('npc_conninfo');
    $this->hasColumn('conninfo_id', 'integer', 4, array (
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
    $this->hasColumn('agent_name', 'string', 32, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(32)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('agent_version', 'string', 8, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(8)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('disposition', 'string', 16, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(16)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('connect_source', 'string', 16, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(16)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('connect_type', 'string', 16, array (
  'alltypes' => 
  array (
    0 => 'string',
  ),
  'ntype' => 'varchar(16)',
  'fixed' => false,
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('connect_time', 'timestamp', null, array (
  'alltypes' => 
  array (
    0 => 'timestamp',
  ),
  'ntype' => 'datetime',
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0000-00-00 00:00:00',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('disconnect_time', 'timestamp', null, array (
  'alltypes' => 
  array (
    0 => 'timestamp',
  ),
  'ntype' => 'datetime',
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0000-00-00 00:00:00',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('last_checkin_time', 'timestamp', null, array (
  'alltypes' => 
  array (
    0 => 'timestamp',
  ),
  'ntype' => 'datetime',
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0000-00-00 00:00:00',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('data_start_time', 'timestamp', null, array (
  'alltypes' => 
  array (
    0 => 'timestamp',
  ),
  'ntype' => 'datetime',
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0000-00-00 00:00:00',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('data_end_time', 'timestamp', null, array (
  'alltypes' => 
  array (
    0 => 'timestamp',
  ),
  'ntype' => 'datetime',
  'values' => 
  array (
  ),
  'primary' => false,
  'default' => '0000-00-00 00:00:00',
  'notnull' => true,
  'autoincrement' => false,
));
    $this->hasColumn('bytes_processed', 'integer', 4, array (
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
    $this->hasColumn('lines_processed', 'integer', 4, array (
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
    $this->hasColumn('entries_processed', 'integer', 4, array (
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
  }


}