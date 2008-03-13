SELECT 
npc_instances.instance_id
,npc_instances.instance_name
,npc_timedeventqueue.event_type
,npc_timedeventqueue.scheduled_time
,npc_timedeventqueue.recurring_event
,obj1.objecttype_id
,npc_timedeventqueue.object_id
,obj1.name1 AS host_name
,obj1.name2 AS service_description
FROM `npc_timedeventqueue`
LEFT JOIN npc_objects as obj1 ON npc_timedeventqueue.object_id=obj1.object_id
LEFT JOIN npc_instances ON npc_timedeventqueue.instance_id=npc_instances.instance_id
ORDER BY scheduled_time ASC, timedeventqueue_id ASC

