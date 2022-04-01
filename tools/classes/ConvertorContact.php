<?php

class ConvertorContact {
  private $contactFetcher;
  private $customDataFetcher;
  private $targetContact;
  private $targetAddress;
  private $targetEmail;
  private $targetPhone;
  private $targetCustomData;

  public function __construct() {
    $this->contactFetcher = new SourceContactFetcher();
    $this->customDataFetcher = new SourceCustomDataFetcher();
    $this->targetContact = new TargetContact();
    $this->targetAddress = new TargetAddress();
    $this->targetEmail = new TargetEmail();
    $this->targetPhone = new TargetPhone();
    $this->targetCustomData = new TargetCustomData();
  }

  public function run() {
    $dao = $this->contactFetcher->getValidMainContacts();
    while ($mainContactInfo = $dao->fetch()) {
      $newMainContactId = $this->processMainContact($mainContactInfo);
      $this->processMainContactCustomFields($mainContactInfo['id'], $newMainContactId);

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
      $mainAddress = $this->contactFetcher->getPrimaryAddress($contact['id']);
      $this->targetAddress->create($newContactId, $mainAddress);

      $dao = $this->contactFetcher->getOtherAddresses($contact['id'], $mainAddress['street_address']);
      while ($otherAddress = $dao->fetch()) {
        $this->targetAddress->createOther($newContactId, $otherAddress);
      }
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

  private function processMainContactCustomFields($oldMainContactId, $newMainContactId) {
    $customGroups = $this->customDataFetcher->getCustomGroupsForContacts();
    foreach ($customGroups as $customGroupId => $customGroupName) {
      $customDataSet = $this->customDataFetcher->getCustomDataSetOfEntity($oldMainContactId, $customGroupId);
      $this->targetCustomData->create($newMainContactId, $customDataSet);
    }
  }

}
