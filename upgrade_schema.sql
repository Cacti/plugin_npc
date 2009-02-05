
CREATE INDEX idx1 ON npc_logentries(entry_time, entry_time_usec);

CREATE INDEX idx1 ON npc_services(config_type);
CREATE INDEX idx2 ON npc_services(host_object_id);
CREATE INDEX idx3 ON npc_services(service_object_id);

CREATE INDEX idx1 ON npc_hosts(host_object_id);
CREATE INDEX idx2 ON npc_hosts(config_type);


CREATE INDEX idx1 ON npc_servicestatus(current_state);

CREATE INDEX idx1 ON npc_hoststatus(current_state);

CREATE INDEX idx1 ON npc_comments(object_id);

CREATE INDEX idx1 ON npc_host_graphs(host_object_id);

CREATE INDEX idx1 ON npc_service_graphs(service_object_id);


CREATE INDEX idx1 ON npc_servicechecks(service_object_id, start_time);
