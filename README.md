# Zekere Zelfzorg

Een demonstratie van digitale zorgbegeleiding voor de CWZ-challenge, ontwikkeld door Zschot Media. De app werkt met fictieve gegevens en duidelijk gemarkeerde voorbeeldstappen. De demo is bedoeld voor productbeoordeling.

## Lokaal draaien

Benodigd zijn PHP 8.2 of hoger met PDO SQLite, Composer 2 en Node 22 of 24.

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=CareDemoHistorySeeder
php artisan serve --host=localhost --port=8000
```

Open een tweede terminal vanaf de repo.

```sh
cd frontend
npm ci
npm run dev -- --host localhost --port 3000
```

Open [de lokale demo](http://localhost:3000). Voor een ander origin stel je `FRONTEND_URL` in de Laravel-omgeving in en `NUXT_PUBLIC_API_BASE_URL` voor Nuxt. Houd deze demo met bekende accounts op een afgeschermde omgeving.

## Demonstreren

Gebruik het wachtwoord `ZekereDemo2026!` voor alle vier fictieve accounts.

| Account | Rol | Onderdeel |
| --- | --- | --- |
| sam@example.test | Mantelzorger | Dagplanning, begeleiding en eigen uitvoering |
| alex@example.test | Patiënt | Logboek en afzonderlijk antwoord op een nazorgcheck |
| robin@example.test | Zorgprofessional | Plannen, demomeldingen en beoordelingen |
| noor@example.test | Invallende mantelzorger | Eigen vaardigheidsstatus en eigen toewijzingen |

1. Log in als Sam. Open de taak van vandaag, volg de voorbeeldstappen en sla de eindcheck op. In het logboek staan ook twee historische uitvoeringen, waarvan één achteraf ingevoerd.
2. Log in als Alex. Er staat al een historische nazorgcheck klaar. Antwoord op de demonstratievraag en kies **Ja** om de ingestelde afwijkingsregel te zien.
3. Log in als Robin. Open de gesimuleerde melding en leg een beoordeling vast. Bekijk ook het verschil tussen de vaardigheidsstatus van Sam en Noor. Voor een nieuwe demonstratietaak kun je de nazorgtermijn op nul minuten zetten.
4. Log in als Noor. Dit account ziet zijn eigen vaardigheidsstatus en kan alleen eigen toegewezen uitvoeringen registreren.

## Status en verificatie

De planning, registraties, nazorgantwoorden en beoordelingen worden in de database opgeslagen. Openstaande nazorgchecks verschijnen in het portaal zodra ze ingevuld kunnen worden. Meldingen blijven binnen het portaal en zijn gesimuleerd. Medische instructies, echte verzending, uploads, uitnodigingen en uitgebreide toetsing vallen buiten deze versie.

`php artisan test` draait de backendtests. `npm run build` controleert de frontendbuild. Zie de [starterkitanalyse](docs/analysis.md), [roadmap](docs/roadmap.md) en [gegevens- en pilotnotities](docs/data-and-pilot.md).
