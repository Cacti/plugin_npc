SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_servicegroups.servicegroup_id
,npc_servicegroups.servicegroup_object_id
,obj1.name1 AS servicegroup_name
,npc_servicegroups.alias AS servicegroup_alias
,npc_services.service_object_id 
,obj2.name1 AS host_name
,obj2.name2 AS service_description
FROM `npc_servicegroups` 
INNER JOIN npc_servicegroup_members ON npc_servicegroups.servicegroup_id=npc_servicegroup_members.servicegroup_id 
INNER JOIN npc_services ON npc_servicegroup_members.service_object_id=npc_services.service_object_id
INNER JOIN npc_objects as obj1 ON npc_servicegroups.servicegroup_object_id=obj1.object_id
INNER JOIN npc_objects as obj2 ON npc_servicegroup_members.service_object_id=obj2.object_id
INNER JOIN npc_instances ON npc_servicegroups.instance_id=npc_instances.instance_id
