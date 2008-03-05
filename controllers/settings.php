<?php
/**
 * State controller class
 *
 * Handles saving and retrieving NPC application state/
 * 
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
 * State controller class
 *
 * Handles reading/writing state events to the
 * npc_settings table.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcSettingsController extends Controller {

    function getSettings($id) {

        $q = new Doctrine_Query();
        $settings = $q->from('NpcSettings s')->where('s.user_id = ?', $id)->execute();
        
        return($settings);

    }

    function save($params) {
    
        $user_id = $_SESSION['sess_user_id'];
        $settings = $this->getSettings($user_id);

        print_r($settings);

        //$rows = $q->update('NpcSettings')
        //          ->set('', 'amount + 200')
    
    }

}
