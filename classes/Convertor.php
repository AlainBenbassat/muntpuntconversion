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
    $targetEmail = new TargetEmail();
    $targetPhone = new TargetPhone();

    $dao = $contactFetcher->getValidMainContacts(0, $this->batchLimit);
    while ($scoredContactInfo = $dao->fetch()) {
      $contact = $contactFetcher->getContact($scoredContactInfo['id']);

      $newContactId = $targetContact->create($contact);
      $targetContact->addOldCiviCRMId($contact['id'], $newContactId);

      if ($scoredContactInfo['heeft_emailadres'] && $scoredContactInfo['email_onhold'] == 0) {
        $targetEmail->create($newContactId, $scoredContactInfo['email']);
      }

      if ($scoredContactInfo['heeft_postadres']) {
        $sourceAddress = $contactFetcher->getPrimaryAddress($contact['id']);
        $targetAddress->create($newContactId, $sourceAddress);
      }

      if ($scoredContactInfo['heeft_telefoonnummer']) {
        $sourcePhones = $contactFetcher->getPhones($contact['id']);
        if (count($sourcePhones)) {
          $targetPhone->create($newContactId, $sourcePhones);
        }
      }

      // contributions
      // events
      // relationships
      // groups
      // mailchimp

    }
  }
}
