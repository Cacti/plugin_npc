SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_statehistory.object_id
,obj1.name1 AS host_name
,npc_statehistory.*
FROM `npc_statehistory`
LEFT JOIN npc_objects as obj1 ON npc_statehistory.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_statehistory.instance_id=npc_instances.instance_id
WHERE obj1.objecttype_id='1'
ORDER BY state_time DESC, state_time_usec DESC

