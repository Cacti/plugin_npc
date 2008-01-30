<?php

class NpcHostsController extends Controller {

    /**
     * Name of the controller.
     *
     * @var string
     */
    var $name = 'NpcHosts';

    /**
     * An array containing the class names of models this controller uses.
     *
     * @var mixed A single name as a string or a list of names as an array.
     * @access protected
     */
    var $models = array('NpcHost');

    private $currentState = array('0' => 'up',
                                  '1' => 'down',
                                  '2' => 'unreachable',
                                  '-1' => 'pending');

    /**
     * getHostSummary
     * 
     * Returns a summary of the state of all hosts.
     *
     * @return array  The host status summary
     */
    function getHostSummary() {

$q = new Doctrine_Query();
$results = $q->from('NpcServices')->execute();



// get all services
//foreach(Doctrine::getTable('NpcServices')->findAll() as $service) {
foreach($results as $service) {
    if (count($service->Comment) > 0) {
        foreach($service->Comment as $comment) {
            print $service->Host->display_name . ": " . $service->display_name . " - " . $comment->entry_time . " - " . $comment->comment_data . "\n";
        }
    }
}
exit;

/*
        $results = $this->hostStatus();

        $status = array('down' => 0, 
                        'unreachable' => 0,
                        'up' => 0,
                        'pending' => 0);

        for($i = 0; $i < count($results); $i++) {
            $status[$this->currentState[$results[$i]['current_state']]]++;
        }

        return(array(1, array($status)));
*/
    }
}
?>
