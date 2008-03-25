<?php

class NpcServicestatus extends BaseNpcServicestatus
{
    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasOne('NpcObjects as Object', array('local' => 'service_object_id', 'foreign' => 'object_id'));
        $this->hasOne('NpcServices as Service', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasOne('NpcServiceGraphs as Graph', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasMany('NpcServicegroups as Servicegroups', array('local' => 'service_object_id', 'foreign' => 'servicegroup_id', 'refClass' => 'NpcServicegroupMembers'));
    }
}
