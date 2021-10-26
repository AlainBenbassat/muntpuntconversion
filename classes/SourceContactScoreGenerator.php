<?php

/**
 * Loops over all source contacts put them in one of these 3 categories:
 *   - migrate (i.e. quaulity of the data is good)
 *   - do not migrate (i.e. low quality contact because it is spam, it has no relevant data...)
 *   - needs (manual) cleanup (i.e. because it's a duplicate...)
 */
class SourceContactScoreGenerator {
  private $batchLimit;

  public function __construct($batchLimit = 200) {
    $this->batchLimit = $batchLimit;
  }

  public function start() {
    $contactFetcher = new SourceContactFetcher();
    $contactValidator = new SourceContactValidator();
    $scoreLogger = new SourceContactLogger(TRUE);

    $dao = $contactFetcher->getBatchAllContacts(0, $this->batchLimit);
    while ($row = $dao->fetch()) {
      $contact = $contactFetcher->getContact($row['id']);
      $rating = $contactValidator->getValidationRating($contact);

      $scoreLogger->logContact($contact, $rating);
    }

    $scoreLogger->printStats();
  }
}
