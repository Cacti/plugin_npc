SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_contacts.contact_object_id
,obj1.name1 AS contact_name
FROM `npc_contacts`
LEFT JOIN npc_objects as obj1 ON npc_contacts.contact_object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_contacts.instance_id=npc_instances.instance_id
WHERE npc_contacts.config_type='1'
ORDER BY instance_name ASC, contact_name ASC

