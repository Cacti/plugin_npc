SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_hosts.host_object_id
,obj1.name1 AS host_name
,npc_hoststatus.*
FROM `npc_hoststatus`
LEFT JOIN npc_objects as obj1 ON npc_hoststatus.host_object_id=obj1.object_id
LEFT JOIN npc_hosts ON npc_hoststatus.host_object_id=npc_hosts.host_object_id
LEFT JOIN npc_instances ON npc_hosts.instance_id=npc_instances.instance_id
WHERE npc_hosts.config_type='1'
ORDER BY instance_name ASC, host_name ASC
