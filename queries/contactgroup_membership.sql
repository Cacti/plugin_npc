SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_contactgroups.contactgroup_id
,npc_contactgroups.contactgroup_object_id
,obj1.name1 AS contactgroup_name
,npc_contactgroups.alias AS contactgroup_alias
,npc_contacts.contact_object_id 
,obj2.name1 AS contact_name
FROM `npc_contactgroups` 
INNER JOIN npc_contactgroup_members ON npc_contactgroups.contactgroup_id=npc_contactgroup_members.contactgroup_id 
INNER JOIN npc_contacts ON npc_contactgroup_members.contact_object_id=npc_contacts.contact_object_id
INNER JOIN npc_objects as obj1 ON npc_contactgroups.contactgroup_object_id=obj1.object_id
INNER JOIN npc_objects as obj2 ON npc_contactgroup_members.contact_object_id=obj2.object_id
INNER JOIN npc_instances ON npc_contactgroups.instance_id=npc_instances.instance_id