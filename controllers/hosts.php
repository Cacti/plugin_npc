<?php

class NpcHostsController extends Controller {

    /**
     * statusCount
     * 
     * Returns a state count for all hosts
     *
     * @return array  The host status summary
     */
    function statusCount() {

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

        return(array(1, array($status)));
    }
}
