#!/bin/bash

CIVI_CREDENTIALS=settings/civi.cnf

echo "Creating event calendar 'Kalenderweergave'..."
mysql --defaults-file="$CIVI_CREDENTIALS" <<EOF
INSERT INTO civicrm_event_calendar (id, calendar_title, show_past_events, show_end_date, show_public_events, events_by_month, event_timings, events_from_month, event_type_filters, week_begins_from_day, recurring_event, enrollment_status) VALUES (1,'Kalenderweergave',1,1,1,0,1,0,1,1,0,0);
EOF
[[ $? != 0 ]] && exit 1

echo "Configuring colors of 'Kalenderweergave'..."
mysql --defaults-file="$CIVI_CREDENTIALS" <<EOF
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (59,1,11,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (60,1,24,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (61,1,3,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (62,1,25,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (63,1,30,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (64,1,26,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (65,1,27,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (66,1,28,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (67,1,31,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (68,1,29,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (69,1,36,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (70,1,44,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (71,1,33,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (72,1,32,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (73,1,9,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (74,1,35,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (75,1,45,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (76,1,6,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (77,1,19,'FFA62F');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (78,1,37,'D68B27');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (79,1,20,'404040');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (80,1,21,'F778A1');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (81,1,39,'6CBB3C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (82,1,48,'38A2FF');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (83,1,43,'3366CC');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (84,1,47,'3366CC');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (85,1,49,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (86,1,50,'FF4C4C');
INSERT INTO civicrm_event_calendar_event_type (id, event_calendar_id, event_type, event_color) VALUES (87,1,51,'FF4C4C');
EOF

echo "Adding event calendar 'Kalenderweergave' to menu..."
mysql --defaults-file="$CIVI_CREDENTIALS" <<EOF
update civicrm_navigation set weight = 0, name = 'Kalenderweergave', label = 'Kalenderweergave', url = 'civicrm/showevents?id=1' where name = 'Show Events Calendar';
EOF
[[ $? != 0 ]] && exit 1
