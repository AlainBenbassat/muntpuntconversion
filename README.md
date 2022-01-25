# Muntpunt Conversie

## Stap 0: Configuratie

De conversie maakt gebruik van de CiviCRM config file uit de nieuwe Drupal9 omgeving.

### Bron database

Voeg 3 defines toe:

 * ICONTACT_DSN
 * ICONTACT_USER
 * ICONTACT_PASSWORD

bv.

    if (!defined('ICONTACT_DSN')) {
      define('ICONTACT_DSN', 'mysql:host=localhost:3306;dbname=icontact;charset=utf8');
      define('ICONTACT_USER', 'my-dbuser');
      define('ICONTACT_PASSWORD', 'my-db-password123');
    }

### Doel database

Standaard CiviCRM connectie (CIVICRM_DSN).

## Stap 1: MailChimp

 * Zip-bestanden downloaden en in de map mailchimp/zip stoppen.
 * e-mailadressen importeren via: php extract_mailchimp.php

Na afloop zijn de tabellen migration_mailchimp_groups en migration_mailchimp_group_contacts aangemaakt en gevuld met data uit de csv-bestanden.

## Stap 2: contacten markeren voor import

 * contacten markeren via: php start.php score_source_contacts

Na afloop is de tabel migration_contacts gevuld met contact id's van te migreren contacten.

## Stap 3: contacten importeren

 * php start convert


