<?php

class SourceProfileFetcher {
  public function getProfilesToMigrate() {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_uf_group
      where
        id in (
          171,
          179,
          143,
          185,
          189,
          239,
          133,
          131
        )
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getProfileFields($profileId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_uf_field
      where
        uf_group_id = $profileId
      and
        is_active = 1
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getEventProfiles($sourceEventId) {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_uf_join
      where
        entity_id = $sourceEventId
      and
        entity_table = 'civicrm_event'
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }
}
