<?php


class NpcEventlogController extends Controller {

    /**
     * getLogEntries
     * 
     * Returns log entries
     *
     * @return array  The host status summary
     */
    function getLogs() {

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->from( 'NpcLogentries l' )
                ->orderby( 'l.entry_time DESC, l.entry_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->jsonOutput($results, $q->getNumResults());
    }
}
