<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class NpcHostgroups extends BaseNpcHostgroups
{
    public function setUp()
    {
        $this->hasOne('NpcObjects as Object', array('local' => 'hostgroup_object_id', 'foreign' => 'object_id'));
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasMany('NpcHostgroupMembers as HostgroupMembers', array('local' => 'hostgroup_id', 'foreign' => 'hostgroup_id'));
        $this->hasMany('NpcHosts as Hosts', array('local' => 'hostgroup_id', 'foreign' => 'host_object_id', 'refClass' => 'NpcHostgroupMembers'));
        $this->hasMany('NpcHoststatus as Hoststatus', array('local' => 'hostgroup_id', 'foreign' => 'host_object_id', 'refClass' => 'NpcHostgroupMembers'));
    }
}