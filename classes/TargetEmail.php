<?php

class TargetEmail {
  public function test() {
    $a = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_contact");
    echo "Numcontacts = $a\n";
  }
}
