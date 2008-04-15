SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_notifications.object_id
,obj1.objecttype_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
,obj2.name1 AS contact_name
,npc_contactnotifications.*
FROM `npc_contactnotifications`
JOIN npc_notifications ON npc_contactnotifications.notification_id=npc_notifications.notification_id
LEFT JOIN npc_objects as obj1 ON npc_notifications.object_id=obj1.object_id
LEFT JOIN npc_objects as obj2 ON npc_contactnotifications.contact_object_id=obj2.object_id
LEFT JOIN npc_instances ON npc_notifications.instance_id=npc_instances.instance_id
ORDER BY start_time DESC, start_time_usec DESC

