SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_commenthistory.object_id
,obj1.objecttype_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
,npc_commenthistory.*
FROM `npc_commenthistory`
LEFT JOIN npc_objects as obj1 ON npc_commenthistory.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_commenthistory.instance_id=npc_instances.instance_id
ORDER BY entry_time DESC, entry_time_usec DESC, commenthistory_id DESC

