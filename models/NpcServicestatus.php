<?php

declare(strict_types=1);

class NpcServicestatus extends BaseNpcServicestatus
{
    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', []);
        $this->hasOne('NpcObjects as Object', []);
        $this->hasOne('NpcServices as Service', []);
        $this->hasOne('NpcServiceGraphs as Graph', []);
        $this->hasMany('NpcServicegroups as Servicegroups', []);
    }
}
