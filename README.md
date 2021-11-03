# Muntpunt Conversie

## Stap 1: MailChimp

 * Zip-bestanden downloaden en in de map mailchimp/zip stoppen.
 * e-mailadressen importeren via: php extract_mailchimp.php

Na afloop zijn de tabellen migration_mailchimp_groups en migration_mailchimp_group_contacts aangemaakt en gevuld met data uit de csv-bestanden.

## Stap 2: contacten markeren voor import

 * contacten markeren via: php start.php score

Na afloop is de tabel migration_contacts gevuld met contact id's van te migreren contacten.

## Stap 3: contacten importeren

 * php start convert


