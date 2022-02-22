<?php

class SourceDB {
  private static $instance = null;

  private $pdo = null;

  private function __construct() {
    if (!defined('ICONTACT_DSN')) {
      throw new \Exception("You need to define ICONTACT_DSN in the Drupal or CiviCRM settings file.");
    }

    if (!defined('ICONTACT_USER')) {
      throw new \Exception("You need to define ICONTACT_USER in the Drupal or CiviCRM settings file.");
    }

    if (!defined('ICONTACT_PASSWORD')) {
      throw new \Exception("You need to define ICONTACT_PASSWORD in the Drupal or CiviCRM settings file.");
    }
  }

  public static function getPDO() {
    if (self::$instance == null) {
      self::$instance = new SourceDB();
      self::connect();
    }

    return self::$instance->pdo;
  }

  private static function connect() {
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    self::$instance->pdo = new PDO(ICONTACT_DSN, ICONTACT_USER, ICONTACT_PASSWORD, $options);
  }
}
