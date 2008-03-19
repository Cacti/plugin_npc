SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_scheduleddowntime.object_id
,obj1.objecttype_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
,npc_scheduleddowntime.*
FROM `npc_scheduleddowntime`
LEFT JOIN npc_objects as obj1 ON npc_scheduleddowntime.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_scheduleddowntime.instance_id=npc_instances.instance_id
ORDER BY scheduled_start_time DESC, scheduleddowntime_id DESC

