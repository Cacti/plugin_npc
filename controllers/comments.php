<?php

class NPC_comments {

    var $id = null;
    var $type = null;
    var $start = 0;
    var $limit = 20;

    private $rowCount;

    /**
     * getComments
     * 
     * Returns a an array of comments
     *
     * @return array  The comments
     */
    function getComments() {

        $sql = "
            SELECT
                npc_instances.instance_id,
                npc_instances.instance_name,
                npc_comments.object_id,
                obj1.name1 AS host_name,
                obj1.name2 AS service_description,
                npc_comments.*
            FROM
                npc_comments
                LEFT JOIN npc_objects as obj1 ON npc_comments.object_id=obj1.object_id
                LEFT JOIN npc_instances ON npc_comments.instance_id=npc_instances.instance_id ";

        if ($this->type) {
            $sql .= " WHERE obj1.objecttype_id = " . $this->type;
        } else {
            $sql .= " WHERE obj1.objecttype_id in (1,2) ";
        }

        if ($this->id) {
            $sql .= sprintf(" AND npc_comments.object_id = %d", $this->id);
        }

        $sql .= " ORDER BY entry_time DESC, entry_time_usec DESC, comment_id DESC";

        $this->rowCount = count(db_fetch_assoc($sql));

        $sql = sprintf($sql . " LIMIT %d,%d", $this->start, $this->limit);

        return(array($this->rowCount, db_fetch_assoc($sql)));
    }

}
?>
