SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_downtimehistory.object_id
,obj1.name1 AS host_name
,npc_downtimehistory.*
FROM `npc_downtimehistory`
LEFT JOIN npc_objects as obj1 ON npc_downtimehistory.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_downtimehistory.instance_id=npc_instances.instance_id
WHERE obj1.objecttype_id='1'
ORDER BY scheduled_start_time DESC, actual_start_time DESC, actual_start_time_usec DESC, downtimehistory_id DESC

