<?php

class TargetContact {

  public function create($contact) {
    $a = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_setting");
    die($a);
    $newContactId = 0;
    return $newContactId;
  }
}
