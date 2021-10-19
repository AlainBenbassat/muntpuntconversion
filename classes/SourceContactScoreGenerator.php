<?php

namespace Muntpuntconversion;

/**
 * Loops over all source contacts put them in one of these 3 categories:
 *   - migrate (i.e. quaulity of the data is good)
 *   - do not migrate (i.e. low quality contact because it is spam, it has no relevant data...)
 *   - needs (manual) cleanup (i.e. because it's a duplicate...)
 */
class SourceContactScoreGenerator {
  private const BATCH_LIMIT = 20000;

  public function start() {
    $contactFetcher = new SourceContactFetcher();
    $contactValidator = new SourceContactValidator();
    $scoreLogger = new SourceContactLogger();

    $dao = $contactFetcher->getBatch(0, self::BATCH_LIMIT);
    while ($row = $dao->fetch()) {
      $contact = $contactFetcher->getContact($row['id']);
      $rating = $contactValidator->getValidationRating($contact);

      switch ($rating['score']) {
        case SourceContactValidator::FINAL_SCORE_MIGRATE:
          $scoreLogger->logMigrate($contact, $rating);
          break;
        case SourceContactValidator::FINAL_SCORE_DO_NOT_MIGRATE:
          $scoreLogger->logDoNotMigrate($contact, $rating);
          break;
        case SourceContactValidator::FINAL_SCORE_NEEDS_CLEANUP:
          $scoreLogger->logNeedsCleanup($contact, $rating);
          break;
        default:
          throw new \Exception('Invalid score');
      }
    }

    $scoreLogger->printStats();
  }
}
