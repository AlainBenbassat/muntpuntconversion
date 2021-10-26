<?php

class Convertor {
  private $batchLimit;

  public function __construct($batchLimit = 200) {
    $this->batchLimit = $batchLimit;
  }

  public function start() {
    $contactFetcher = new SourceContactFetcher();
    $targetContact = new TargetContact();
    $targetAddress = new TargetAddress();

    $dao = $contactFetcher->getBatchOnlyValidContacts(0, $this->batchLimit);
    while ($scoredContactInfo = $dao->fetch()) {
      $contact = $contactFetcher->getContact($scoredContactInfo['id']);

      $newContactId = $targetContact->create($contact);
      $targetAddress->add($newContactId, $scoredContactInfo, $contact);
    }
  }
}
