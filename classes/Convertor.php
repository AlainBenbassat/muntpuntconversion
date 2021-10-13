<?php

namespace Muntpuntconversion;

class Convertor {
  public function start() {
    $contactFetcher = new SourceContactFetcher();
    $contactValidator = new SourceContactValidator();

    $dao = $contactFetcher->getBatch(0, 20000);
    while ($row = $dao->fetch()) {
      $contact = $contactFetcher->getContact($row['id']);
      if ($contactValidator->isValidContact($contact)) {
        //$contactImporter->importContact($contact);
      }
    }
  }

}
