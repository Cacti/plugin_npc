<?php
/**
 * Hosts controller class
 *
 * This is the access point to the npc_hosts table.
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @subpackage          npc.controllers
 * @since               NPC 2.0
 * @version             $Id: $
 */

/**
 * Hosts controller class
 *
 * Hosts controller provides basic functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcHostsController extends Controller {

    /**
     * summary
     * 
     * Returns a state count for all hosts
     *
     * @return array  The host status summary
     */
    function summary() {

        $status = array(
            'down'        => 0, 
            'unreachable' => 0,
            'up'          => 0,
            'pending'     => 0
        );

        $q = new Doctrine_Query();
        $hosts = $q->from('NpcHosts h')->where('h.config_type = 1')->execute();

        foreach($hosts as $host) {
            $status[$this->hostState[$host->Status->current_state]]++;
        }

        $this->jsonOutput($status);
    }
}
