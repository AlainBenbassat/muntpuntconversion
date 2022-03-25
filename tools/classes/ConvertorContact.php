<?php

class ConvertorContact {
  private $contactFetcher;
  private $targetContact;
  private $targetAddress;
  private $targetEmail;
  private $targetPhone;

  public function __construct() {
    $this->contactFetcher = new SourceContactFetcher();
    $this->targetContact = new TargetContact();
    $this->targetAddress = new TargetAddress();
    $this->targetEmail = new TargetEmail();
    $this->targetPhone = new TargetPhone();
  }

  public function run() {
    $dao = $this->contactFetcher->getValidMainContacts();
    while ($mainContactInfo = $dao->fetch()) {
      $newMainContactId = $this->processMainContact($mainContactInfo);

      $daoDupes = $this->contactFetcher->getDuplicateContacts($mainContactInfo['id']);
      while ($duplicateContactInfo = $daoDupes->fetch()) {
        $this->processDuplicateContact($newMainContactId, $duplicateContactInfo);
      }
    }
  }

  private function processMainContact($contactInfo) {
    $contact = $this->contactFetcher->getContact($contactInfo['id']);
    $newContactId = $this->targetContact->create($contact);

    $this->targetContact->addOldCiviCRMId($contact['id'], $newContactId);

    if ($contactInfo['heeft_emailadres'] && $contactInfo['email_onhold'] == 0) {
      $this->targetEmail->create($newContactId, $contactInfo['email']);
    }

    if ($contactInfo['heeft_postadres']) {
      $sourceAddress = $this->contactFetcher->getPrimaryAddress($contact['id']);
      $this->targetAddress->create($newContactId, $sourceAddress);
    }

    if ($contactInfo['heeft_telefoonnummer']) {
      $sourcePhones = $this->contactFetcher->getPhones($contact['id']);
      if (count($sourcePhones)) {
        $this->targetPhone->create($newContactId, $sourcePhones);
      }
    }

    return $newContactId;
  }

  private function processDuplicateContact($mainContactId, $contactInfo) {
    $contact = $this->contactFetcher->getContact($contactInfo['id']);
    $this->targetContact->merge($mainContactId, $contact);

    $this->targetContact->addOldCiviCRMId($contact['id'], $mainContactId);

    if ($contactInfo['heeft_postadres']) {
      $sourceAddress = $this->contactFetcher->getPrimaryAddress($contact['id']);
      $this->targetAddress->merge($mainContactId, $sourceAddress);
    }

    if ($contactInfo['heeft_telefoonnummer']) {
      $sourcePhones = $this->contactFetcher->getPhones($contact['id']);
      if (count($sourcePhones)) {
        $this->targetPhone->merge($mainContactId, $sourcePhones);
      }
    }
  }

}
