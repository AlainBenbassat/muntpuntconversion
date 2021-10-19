<?php

namespace Muntpuntconversion;

class Convertor {
  private const BATCH_LIMIT = 200;

  public function Start() {
    $contactFetcher = new SourceContactFetcher(TRUE);
    $targetContact = new TargetContact();

    $dao = $contactFetcher->getBatch(0, self::BATCH_LIMIT);
    while ($row = $dao->fetch()) {
      $contact = $contactFetcher->getContact($row['id']);
    }
  }
}
