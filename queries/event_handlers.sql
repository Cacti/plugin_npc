SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_eventhandlers.object_id
,obj1.objecttype_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
,npc_eventhandlers.*
FROM `npc_eventhandlers`
LEFT JOIN npc_objects as obj1 ON npc_eventhandlers.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_eventhandlers.instance_id=npc_instances.instance_id
ORDER BY start_time DESC, start_time_usec DESC, eventhandler_id DESC

