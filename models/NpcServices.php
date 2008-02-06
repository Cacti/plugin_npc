<?php

class NpcServices extends BaseNpcServices
{

    public function setUp() 
    {

        $this->hasOne('NpcObjects as Object', array('local' => 'service_object_id', 'foreign' => 'object_id'));
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasOne('NpcHosts as Host', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
        $this->hasOne('NpcServicestatus as Status', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasMany('NpcComments as Comment', array('local' => 'service_object_id', 'foreign' => 'object_id'));
        $this->hasMany('NpcServicechecks as Check', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        //$this->hasMany('NpcServicegroupMembers as ServicegroupMembers', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasMany('NpcServicegroups as Groups', array('local' => 'service_object_id', 'foreign' => 'servicegroup_id', 'refClass' => 'NpcServicegroupMembers'));
    }


}
