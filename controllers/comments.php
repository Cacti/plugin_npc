<?php
/**
 * Comments controller class
 *
 * This is the access point to the npc_comments table.
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
 * Comments controller class
 *
 * Comments controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcCommentsController extends Controller {

    /**
     * getAck
     * 
     * An accessor method to retrieve an acknowlegement
     *
     * @return string   The acknowledgement
     */
    function getAck($id) {
        $where = 'c.entry_type = 4';
        $ack = $this->comments($id, $where);
        return($ack[0]['author_name'] . "*|*" . $ack[0]['comment_data']);
    }

    /**
     * getLastComment
     * 
     * An accessor method to retrieve the latest comment
     * that is not an aknowledgement.
     *
     * @return string   The comment
     */
    function getLastComment($id) {
        $where = 'c.entry_type != 4';
        $comment = $this->comments($id, $where);
        if (isset($comment[0])) {
            return($comment[0]['author_name'] . "*|*" . $comment[0]['comment_data']);
        }

        return(0);
    }

    /**
     * getComments
     * 
     * An accessor method to return comments
     *
     * @return string   json output
     */
    function getComments() {
        //$response['response']['value']['items'] = $this->comments();
        //$response['response']['value']['total_count'] = $this->numRecords;
        //$response['response']['value']['version']     = 1;

        //return(json_encode($response));
	return($this->jsonOutput($this->comments()));
    }

    /**
     * getHostComments
     * 
     * An accessor method to return all host comments
     *
     * @return string   json output
     */
    function getHostComments() {
        $results = $this->flattenArray($this->comments(null, 'o.objecttype_id = 1'));
        return($this->jsonOutput($results));
    }

    /**
     * getServiceComments
     * 
     * An accessor method to return all service comments
     *
     * @return string   json output
     */
    function getServiceComments() {
        $results = $this->flattenArray($this->comments(null, 'o.objecttype_id = 2'));
        return($this->jsonOutput($results));
    }

    /**
     * deleteAllHostComments
     * 
     * Delete all host comments.
     *
     * @return string   json output
     */
    function deleteAllHostComments() {

        require_once("plugins/npc/controllers/nagios.php");

        $seen = array();

        $results = $this->flattenArray($this->comments(null, 'o.objecttype_id = 1'));
        
        for ($i = 0; $i < count($results); $i++) {
            $host = $results[$i]['host_name'];
            if (!isset($seen[$host])) {
                $params = array(
                    'command' => 'DEL_ALL_HOST_COMMENTS',
                    'host_name' => $host
                );
                NpcNagiosController::command($params);
                $seen[$host] = 1;
            }   
        }
    }

    /**
     * deleteAllServiceComments
     * 
     * Delete all service comments.
     *
     * @return string   json output
     */
    function deleteAllServiceComments() {

        require_once("plugins/npc/controllers/nagios.php");

        $seen = array();

        $results = $this->flattenArray($this->comments(null, 'o.objecttype_id = 2'));
        
        for ($i = 0; $i < count($results); $i++) {
            $host = $results[$i]['host_name'];
            $service = $results[$i]['service_description'];
            if (!isset($seen[$host][$service])) {
                $params = array(
                    'command' => 'DEL_ALL_SVC_COMMENTS',
                    'host_name' => $host,
                    'service_description' => $service,
                );
                NpcNagiosController::command($params);
                $seen[$host][$service] = 1;
            }   
        }
    }

    /**
     * comments
     * 
     * Returns a an array of comments
     *
     * @return array  The comments
     */
    function comments($id=null, $where='') {

        if ($this->id || $id) {
            if ($where != '') {
                $where .= ' AND ';
            }
            $where .= sprintf("c.object_id = %d", is_null($id) ? $this->id : $id);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'c.*')
                ->from('NpcComments c')
                ->leftJoin('c.Object o')
                ->leftJoin('c.Instance i')
                ->where("$where")
                ->orderby( 'c.entry_time DESC, c.entry_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        // Set the total number of records 
        $this->numRecords = $q->getNumResults();

        return($results);
    }

}
