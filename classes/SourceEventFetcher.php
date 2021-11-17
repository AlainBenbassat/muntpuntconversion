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
        )
      order by
        id
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }
}
