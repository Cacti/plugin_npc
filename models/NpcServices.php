<?php

declare(strict_types=1);
/**
 * NpcServices class
 *
 * This is the access point to the npc_services table
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @subpackage          npc.models
 * @since               NPC 2.0
 * @version             $Id$
 */

/**
 * NpcServices class
 *
 * NpcServices class handles mapping the table associations.
 * 
 * @package     npc
 * @subpackage  npc.models
 */
class NpcServices extends BaseNpcServices
{

    public function setUp() 
    {

        $this->hasOne('NpcObjects as Object', []);
        $this->hasOne('NpcInstances as Instance', []);
        $this->hasOne('NpcHosts as Host', []);
        $this->hasOne('NpcHoststatus as Hoststatus', []);
        $this->hasOne('NpcServicestatus as Status', []);
        $this->hasOne('NpcServiceGraphs as Graph', []);
        $this->hasMany('NpcComments as Comment', []);
        $this->hasMany('NpcServicechecks as Check', []);
        $this->hasMany('NpcServicegroups as Groups', []);
    }


}
