<?php

declare(strict_types=1);

class NpcServicegroupMembers extends BaseNpcServicegroupMembers
{
    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', []);
        $this->hasOne('NpcObjects as Object', []);
    }
}
