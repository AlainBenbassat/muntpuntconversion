<?php

class Convertor {
  private const BATCH_LIMIT = 200;

  public function start() {
    $contactFetcher = new SourceContactFetcher();
    $targetContact = new TargetContact();
    $targetAddress = new TargetAddress();

    $dao = $contactFetcher->getBatchOnlyValidContacts(0, self::BATCH_LIMIT);
    while ($scoredContactInfo = $dao->fetch()) {
      $contact = $contactFetcher->getContact($scoredContactInfo['id']);

      $newContactId = $targetContact->create($contact);
      $targetAddress->add($newContactId, $scoredContactInfo, $contact);
    }
  }
}
