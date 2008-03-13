SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_services.host_object_id
,obj1.name1 AS host_name
,npc_services.service_object_id
,obj1.name2 AS service_description
,npc_servicestatus.*
FROM `npc_servicestatus`
LEFT JOIN npc_objects as obj1 ON npc_servicestatus.service_object_id=obj1.object_id
LEFT JOIN npc_services ON npc_servicestatus.service_object_id=npc_services.service_object_id
LEFT JOIN npc_instances ON npc_services.instance_id=npc_instances.instance_id
WHERE npc_services.config_type='1'
ORDER BY instance_name ASC, host_name ASC, service_description ASC
