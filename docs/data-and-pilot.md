# Gegevens, toegang en pilotvoorwaarden

| Gegevens | Bron en betekenis |
| --- | --- |
| Zorgprofielen en leden | De koppeling tussen account en patiëntprofiel bepaalt de rol binnen dat profiel. |
| Toewijzingen | De professional stelt uitvoerder, aanvang, optioneel einde, tijdstip en herhaling in. De planning rekent in Amsterdamse tijd. Voor meerdere dagelijkse tijdstippen worden aparte opdrachten gemaakt. |
| Uitvoeringen | Het account, instructieversie, registratietijd, eventuele uitvoertijd en antwoorden worden bewaard. Eén zelfregistratie per opdracht en zorgdag. Geopende stappen beschrijven app-interactie. |
| Nazorgchecks | Termijn, vraag, antwoordbevoegdheid en regel worden voor het controlemoment vastgelegd. Het antwoord en de observator staan los van de uitvoering. UTC wordt gebruikt voor tijdstippen in opslag. |
| Meldingen en beoordelingen | Eindcheck of nazorg kan een gesimuleerde melding aanmaken. Een gekoppelde zorgprofessional kan een review vastleggen. Persoonsgebonden demobeoordelingen hebben een eigen historie. |

De app hergebruikt Laravel en Sanctum voor de API en Nuxt voor de schermen. Het demosjabloon staat in de back-end. Bij toekomstige sjabloonwijzigingen moet elke gebruikte versie beschikbaar blijven in een onveranderlijk versieregister. De demo gebruikt één versie.

Het portaal ondersteunt een lokale demonstratie. Beeldmateriaal, uitnodigingsbeheer, push- en e-mailherinneringen, correcties op bestaande registraties, oefentoetsen en externe zorgkoppelingen zijn nog niet geïmplementeerd.

Voor een zorgpilot moeten het betrokken zorgteam en de verantwoordelijke organisatie onder meer de protocolversie, materiaalrechten, bekwaamheidscriteria, tijdkritische contactroutes en verantwoordelijkheid voor opvolging vastleggen. Besluiten over privacy, toepasselijke regels, identiteitscontrole, bewaartermijnen, audit, beveiliging, incidentafhandeling en eventuele classificatie vereisen afzonderlijke beoordeling. De demoaccounts, lokale opslagkeuze en browsergebonden bearer-sessie zijn bedoeld voor de demonstratie. Beoordeel de sessiearchitectuur en de omgang met persoonsgegevens vóór een pilot.

De bediening is ontworpen met semantische vormen, tekstlabels, toetsfocus en schaalbare lay-out. Voor gebruik in een pilot zijn gebruikerstests en een toegankelijkheidstoets met hulpmiddelen nodig.
