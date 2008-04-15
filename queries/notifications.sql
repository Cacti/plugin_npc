SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_notifications.object_id
,obj1.objecttype_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
,npc_notifications.*
FROM `npc_notifications`
LEFT JOIN npc_objects as obj1 ON npc_notifications.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_notifications.instance_id=npc_instances.instance_id
ORDER BY start_time DESC, start_time_usec DESC

