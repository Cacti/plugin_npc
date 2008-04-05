<?php
/**
 * NpcLogentries class
 *
 * This is the access point to the npc_logentries table
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
 * NpcLogentries class
 *
 * This is the access point to the npc_logentries table
 * 
 * @package     npc
 * @subpackage  npc.models
 */
class NpcLogentries extends BaseNpcLogentries
{

    public function setUp()
    {
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
    }
}

