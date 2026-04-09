<?php

declare(strict_types=1);

class NpcDowntimehistory extends BaseNpcDowntimehistory
{
    public function setUp()
    {
        $this->hasOne('NpcObjects as Object', []);
        $this->hasOne('NpcInstances as Instance', []);
        $this->hasOne('NpcServices as Service', []);
        $this->hasOne('NpcHosts as Host', []);
    }
}
