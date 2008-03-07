<?php

class NpcHoststatus extends BaseNpcHoststatus
{
    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasOne('NpcObjects as Object', array('local' => 'host_object_id', 'foreign' => 'object_id'));
        $this->hasOne('NpcHosts as Host', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
        $this->hasMany('NpcHostgroups as Hostgroups', array('local' => 'host_object_id', 'foreign' => 'hostgroup_id', 'refClass' => 'NpcHostgroupMembers'));
        $this->hasMany('NpcServices as Services', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
    }
}
