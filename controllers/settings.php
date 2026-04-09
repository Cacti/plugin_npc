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
 * @version             $Id$
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
        $settings = db_fetch_row_prepared('SELECT *
            FROM npc_settings
            WHERE user_id = ?',
            array($id));

        return($settings);
    }

    function save($params) {

        $user_id = $_SESSION['sess_user_id'];
        $obj = $this->getSettings($user_id);

        $settings = unserialize($obj['settings']);
        if (isset($params['name'])) {
            $settings[$params['name']] = $params['value'];
        }

        db_execute_prepared('UPDATE npc_settings
            SET settings = ?
            WHERE user_id = ?',
            array(serialize($settings), $user_id));

        return(true);
    }

}
