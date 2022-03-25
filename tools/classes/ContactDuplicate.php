<?php

class ContactDuplicate {
  public function run() {
    $duplicateFinder = new SourceContactDuplicateFinder();
    $duplicateFinder->markMainContacts();

    $scoreGenerator = new SourceContactScoreGenerator();
    $scoreGenerator->validateEmployers();

    $logger = new SourceContactLogger();
    $logger->printStats();
  }
}
