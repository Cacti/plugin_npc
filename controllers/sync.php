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
 * Hosts controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcSyncController extends Controller {

    /**
     * import
     * 
     * The intro page for N2C
     *
     * @return string   The html output
     */
    function import($params) {
   
        $data = json_decode($params['data']);
        exec("echo \"" . print_r($data, true) . "\" > /tmp/DEBUG");

        return($params['data']);
    }

}
