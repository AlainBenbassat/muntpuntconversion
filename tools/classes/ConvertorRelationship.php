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
      $this->targetRelationship->createRelationship($employeeRelationship);
    }
  }
}
