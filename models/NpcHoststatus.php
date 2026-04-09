<?php

declare(strict_types=1);

class NpcHoststatus extends BaseNpcHoststatus
{
    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', []);
        $this->hasOne('NpcObjects as Object', []);
        $this->hasOne('NpcHosts as Host', []);
        $this->hasOne('NpcHostGraphs as Graph', []);
        $this->hasMany('NpcHostgroups as Hostgroups', []);
        $this->hasMany('NpcServices as Services', []);
    }
}
