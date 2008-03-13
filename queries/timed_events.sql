SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_timedevents.event_type
,npc_timedevents.scheduled_time
,npc_timedevents.event_time
,npc_timedevents.event_time_usec
,npc_timedevents.recurring_event
,obj1.objecttype_id
,npc_timedevents.object_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
FROM `npc_timedevents`
LEFT JOIN npc_objects as obj1 ON npc_timedevents.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_timedevents.instance_id=npc_instances.instance_id
WHERE scheduled_time < NOW()
ORDER BY scheduled_time DESC, timedevent_id DESC

