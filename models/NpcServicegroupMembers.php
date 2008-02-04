<?php

class NpcServicegroupMembers extends BaseNpcServicegroupMembers
{
    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasOne('NpcObjects as Object', array('local' => 'service_object_id', 'foreign' => 'object_id'));
    }
}
