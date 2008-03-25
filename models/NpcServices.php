<?php
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
 * @version             $Id: $
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

        $this->hasOne('NpcObjects as Object', array('local' => 'service_object_id', 'foreign' => 'object_id'));
        $this->hasOne('NpcInstances as Instance', array('local' => 'instance_id', 'foreign' => 'instance_id'));
        $this->hasOne('NpcHosts as Host', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
        $this->hasOne('NpcHoststatus as Hoststatus', array('local' => 'host_object_id', 'foreign' => 'host_object_id'));
        $this->hasOne('NpcServicestatus as Status', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasOne('NpcServiceGraphs as Graph', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasMany('NpcComments as Comment', array('local' => 'service_object_id', 'foreign' => 'object_id'));
        $this->hasMany('NpcServicechecks as Check', array('local' => 'service_object_id', 'foreign' => 'service_object_id'));
        $this->hasMany('NpcServicegroups as Groups', array('local' => 'service_object_id', 'foreign' => 'servicegroup_id', 'refClass' => 'NpcServicegroupMembers'));
    }


}
