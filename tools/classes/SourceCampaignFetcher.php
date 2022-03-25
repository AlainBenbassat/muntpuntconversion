<?php

class SourceCampaignFetcher {
  public function getCampaignsToMigrate() {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_campaign
      where
        start_date >= '2019-01-01'
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

  public function getCampaignTypesToMigrate() {
    $pdo = SourceDB::getPDO();

    $sql = "
      select
        *
      from
        civicrm_option_value
      where
        option_group_id = 52
      and
        value in (select distinct campaign_type_id from civicrm_campaign where start_date >= '2019-01-01')
      order by
        value
    ";

    $dao = $pdo->query($sql);

    return $dao;
  }

}
