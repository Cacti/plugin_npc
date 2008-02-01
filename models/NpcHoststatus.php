<?php

class NpcHoststatus extends BaseNpcHoststatus
{
    public function setUp()
    {
        $this->hasOne('NpcHosts as Host', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
    }
}
