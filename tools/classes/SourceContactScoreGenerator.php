<?php

/**
 * Loops over all source contacts put them in one of these 3 categories:
 *   - migrate (i.e. quaulity of the data is good)
 *   - do not migrate (i.e. low quality contact because it is spam, it has no relevant data...)
 *   - needs (manual) cleanup (i.e. because it's a duplicate...)
 */
class SourceContactScoreGenerator {
  public function validateAllContacts() {
    $contactFetcher = new SourceContactFetcher();
    $contactValidator = new SourceContactValidator();
    $scoreLogger = new SourceContactLogger();
    $scoreLogger->clearLogTableContacts();

    $dao = $contactFetcher->getAllContacts();
    $i = 0;
    while ($row = $dao->fetch()) {
      $i++;
      //echo "$i. Processing contact with id = " . $row['id'] . "\n";
      $contact = $contactFetcher->getContact($row['id']);
      $rating = $contactValidator->getRating($contact);

      $scoreLogger->logContact($contact, $rating);
    }
  }

  public function validateEmployers() {
    $scoreLogger = new SourceContactLogger();
    $scoreLogger->logEmployers();
  }
}
