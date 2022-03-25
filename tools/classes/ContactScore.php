<?php

class ContactScore {
  public function run() {
    $scoreGenerator = new SourceContactScoreGenerator();
    $scoreGenerator->validateAllContacts();

    $logger = new SourceContactLogger();
    $logger->printStats();
  }
}
