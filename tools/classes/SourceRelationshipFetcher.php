<?php

class SourceRelationshipFetcher {

  public function getAllEmployeeRelationshipsToMigrate() {
    $pdo = SourceDB::getPDO();

    $sql = "
      SELECT
        r.*
      FROM
        civicrm_relationship r
      inner join
        civicrm_relationship_type rt on r.relationship_type_id = rt.id and rt.name_a_b = 'Employee of'
      inner join
        migration_contacts mc on mc.id = r.contact_id_a
      where
        mc.score = 1
      and
        mc.heeft_actieve_relaties = 1
      and
        r.relationship_type_id = 4
      and
        r.is_active = 1
      order by
        r.id
    ";
    $dao = $pdo->query($sql);

    return $dao;
  }

}


