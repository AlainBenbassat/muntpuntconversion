<?php

class SourceEventFetcher {
  public function getAllEventsToMigrate() {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        e.*
      FROM
        civicrm_event e
      where
        id in (
          select
            distinct p.event_id
          from
            civicrm_participant p
          inner join
            migration_contacts mc on mc.id = p.contact_id
          where
            mc.score = 1
          and
            mc.heeft_deelgenomen_aan_evenementen = 1
        ) AND ID > 17391
      order by
        id
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getEventParticipants($sourceEventId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        p.*
      from
        civicrm_participant p
      inner join
        migration_contacts mc on mc.id = p.contact_id
      where
        mc.score = 1
      and
        p.event_id = $sourceEventId
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getEventTypes() {
    $pdo = SourceDB::getPDO();
    $sql = "
      select
        ov.*
      from
        civicrm_option_value ov
      inner join
        civicrm_option_group og on ov.option_group_id = og.id and og.name = 'event_type'
      order by
        value
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }
}
