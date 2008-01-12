SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_hostgroups.hostgroup_id
,npc_hostgroups.hostgroup_object_id
,obj1.name1 AS hostgroup_name
,npc_hostgroups.alias AS hostgroup_alias
,npc_hosts.host_object_id 
,obj2.name1 AS host_name
FROM `npc_hostgroups` 
INNER JOIN npc_hostgroup_members ON npc_hostgroups.hostgroup_id=npc_hostgroup_members.hostgroup_id 
INNER JOIN npc_hosts ON npc_hostgroup_members.host_object_id=npc_hosts.host_object_id
INNER JOIN npc_objects as obj1 ON npc_hostgroups.hostgroup_object_id=obj1.object_id
INNER JOIN npc_objects as obj2 ON npc_hostgroup_members.host_object_id=obj2.object_id
INNER JOIN npc_instances ON npc_hostgroups.instance_id=npc_instances.instance_id
