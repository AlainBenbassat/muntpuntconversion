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

  public function getLocBlock($locBlockId) {
    $pdo = SourceDB::getPDO();
    $sql = "
      select
        lb.id,
        lb.address_id,
        lb.email_id,
        lb.phone_id,
        a.street_address,
        a.supplemental_address_1,
        a.supplemental_address_2,
        a.supplemental_address_3,
        a.city,
        a.postal_code,
        a.country_id,
        a.name,
        e.email,
        p.phone
      from
        civicrm_loc_block lb
      left outer join
        civicrm_address a on a.id = lb.address_id
      left outer join
        civicrm_email e on e.id = lb.email_id
      left outer join
        civicrm_phone p on p.id = lb.phone_id
      where
        lb.id = $locBlockId
    ";

    $dao = $pdo->query($sql);

    return $dao->fetch();
  }

  public function getEventCustomFields($sourceEventId, $customGroupName) {
    $pdo = SourceDB::getPDO();
    $sql = "select table_name from civicrm_custom_group where name = '$customGroupName'";
    $dao = $pdo->query($sql);

    if ($row = $dao->fetch()) {
      $sql = "select * from " . $row['table_name'] . " where entity_id = $sourceEventId";
      $dao = $pdo->query($sql);
      return $dao->fetch();
    }
    else {
      throw new Exception("Cannot find table name of custom group $customGroupName");
    }
  }
}
