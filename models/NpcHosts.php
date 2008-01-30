<?php

class NpcHosts extends BaseNpcHosts
{
    public function setUp()
    {
        $this->hasOne('NpcObjects as Object', array('local' => 'host_object_id', 'foreign' => 'object_id'));
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasOne('NpcHoststatus as Status', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
        $this->hasMany('NpcServices as Service', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
    }
}
