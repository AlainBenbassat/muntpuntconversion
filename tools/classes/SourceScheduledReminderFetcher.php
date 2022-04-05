<?php

class SourceScheduledReminderFetcher {
  public function getScheduledRemindersForEvent($eventId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        *
      FROM
        civicrm_action_schedule
      where
        entity_value = $eventId
      and
        mapping_id in (3, 5)
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }
}
