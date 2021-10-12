<?php

namespace Muntpuntconversion;

class Convertor {
  public function start() {
  }

  public function test() {
    $pdo = \Muntpuntconversion\SourceDB::getPDO();

    $sql = "
      SELECT
        first_name,
        last_name
      FROM
        civicrm_contact
      where
        first_name is not null
      and
        contact_type = 'Individual'
      limit 0,20
    ";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
      echo $row['last_name'] . ', ' . $row['first_name'] . "\n";
    }
  }
}
