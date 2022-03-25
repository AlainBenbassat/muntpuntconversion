<?php

class ConvertorRelationship {
  private $relationshipFetcher;
  private $targetRelationship;

  public function __construct() {
    $this->relationshipFetcher = new SourceRelationshipFetcher();
    $this->targetRelationship = new TargetRelationship();
  }

  public function run() {
    $dao = $this->relationshipFetcher->getAllEmployeeRelationshipsToMigrate();
    while ($employeeRelationship = $dao->fetch()) {
      echo 'Converting employee relationship between ' . $employeeRelationship['contact_id_a'] . ' and ' . $employeeRelationship['contact_id_b'] . "...\n";

      $this->targetRelationship->createRelationship($employeeRelationship);
    }
  }
}
