<?php

declare(strict_types=1);

class NpcHosts extends BaseNpcHosts
{
    public function setUp()
    {
        $this->hasOne('NpcObjects as Object', []);
        $this->hasOne('NpcInstances as Instance', []);
        $this->hasOne('NpcHoststatus as Status', []);
        $this->hasMany('NpcServices as Service', []);
        $this->hasMany('NpcHostchecks as Check', []);
        $this->hasMany('NpcHostgroups as Hostgroup', []);
    }
}
