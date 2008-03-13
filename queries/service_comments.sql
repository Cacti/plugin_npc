SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_comments.object_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
,npc_comments.*
FROM `npc_comments`
LEFT JOIN npc_objects as obj1 ON npc_comments.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_comments.instance_id=npc_instances.instance_id
WHERE obj1.objecttype_id='2'
ORDER BY entry_time DESC, entry_time_usec DESC, comment_id DESC

