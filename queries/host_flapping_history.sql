SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_flappinghistory.object_id
,obj1.name1 AS host_name
,npc_flappinghistory.*
FROM `npc_flappinghistory`
LEFT JOIN npc_objects as obj1 ON npc_flappinghistory.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_flappinghistory.instance_id=npc_instances.instance_id
WHERE obj1.objecttype_id='1'
ORDER BY event_time DESC, event_time_usec DESC, flappinghistory_id DESC

