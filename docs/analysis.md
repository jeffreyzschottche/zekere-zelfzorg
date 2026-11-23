# Fase 0 — Starterkit en architectuur

De GitHub-repo bevat een Nuxt/Vue-frontend met Pinia, Tailwind en accountscreens, plus een Laravel 12-API met Sanctum, SQLite-configuratie en routes voor registratie, inloggen en wachtwoordherstel. Er bestaat nog geen zorgdomein of testscenario voor de challenge.

Hergebruik de apps en API. Bewaar zorgrelaties, opdrachten, registraties, nazorg en professionele beoordelingen centraal in Laravel. Nuxt verzorgt de presentatie en haalt gegevens op via de geauthenticeerde API. Elke domeinactie controleert server-side de zorgrelatie en rol. Toegang tot een zorgprofiel verleent op zichzelf geen medische bevoegdheid.

Gebruik SQLite voor een lokale demo met fictieve accounts. Instructieversies en controleconfiguratie worden bij een opname vastgelegd. Zelfrapportage, observatie, melding en professionele beoordeling hebben eigen gegevens en statussen. Planning gebruikt Europe/Amsterdam voor zorgdagen en bewaart tijdstippen in UTC.

Er is geen goedgekeurde instructie of beeldlicentie meegeleverd. Gebruik uitsluitend tekstuele demostappen en simulatiemeldingen. Voor een pilot zijn goedgekeurde inhoud, gegevensbescherming, authenticatiebeleid en afgesproken klinische opvolging nodig.
