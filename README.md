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

### Config items

De extensie https://github.com/AlainBenbassat/be.muntpunt.muntpuntconfig bevat de configuratie-items in de map "resources".

De config items worden gegenereerd via:


    php chelper.php

De extensie bevat een admin scherm om de config items aan te maken en andere dingen in te stellen: civicrm/muntpunt-config-admin
## Stap 1: MailChimp

 * Zip-bestanden downloaden en in de map mailchimp/zip stoppen.
 * e-mailadressen importeren via:


    php extract_mailchimp.php

Na afloop zijn de tabellen migration_mailchimp_groups en migration_mailchimp_group_contacts aangemaakt en gevuld met data uit de csv-bestanden.

## Stap 2: contacten markeren voor import

Vervolgens moeten alle contacten geanalyseerd worden om te zien of we ze al dan niet gaan migraren.

We migreren contacten:

 * met een e-mailadres dat niet op "on hold" of op "optout" staat en geen spam is
 * en minstens een van volgende criteria heeft:
   * recente activiteiten
   * lid van een van de opgegeven groepen
   * persmedewerkers
   * mailchimp contact
   * evenement partner / organisator / contactpersoon

Om aan te duiden welke contacten gemigreerd moeten worden voer je uit:


    php start.php score_source_contacts

Na afloop is de tabel **migration_contacts** gevuld met contact id's van te migreren contacten en de reden waarom een contact al dan niet gemigreerd wodt.


## Stap 3: dubbele contacten aanduiden

De database bevat veel dubbele contacten. Voor we contacten gaan migreren, gaan we in de tabel **migration_contacts** de dubbels aanduiden.

Voer uit:


    php start.php mark_duplicates

## Stap 4: data migeren

De civicrm data wordt in stappen gemigreerd:


    php start.php convert_contacts
    php start.php convert_relationships
    php start.php convert_event_types_roles_status
    php start.php convert_events

We migreren alle toekomstige evenementen + afgelopen evenementen met deelnemers die we gaan migreren.
