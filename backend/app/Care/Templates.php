<?php

namespace App\Care;

class Templates
{
    public const NIGHT_BAG = 'night-bag';

    public function title(string $key): string
    {
        abort_unless($key === self::NIGHT_BAG, 404);
        return 'Nachtzak bij een katheterdagzak';
    }
    public function instruction(string $key): array
    {
        return [
            'title' => $this->title($key),
            'version' => 'demo-1',
            'faq' => 'Deze demonstratie laat zien hoe je uitleg kunt bekijken en een uitvoering kunt registreren. Hier komt na goedkeuring de uitleg van het zorgteam.',
            'steps' => [
                ['id' => 'prepare', 'title' => 'Bekijk de uitleg', 'body' => 'Hier verschijnt de goedgekeurde voorbereiding.', 'help' => 'Hier kan het zorgteam uitleg over de benodigdheden toevoegen.'],
                ['id' => 'guide', 'title' => 'Doorloop de demonstratie', 'body' => 'Hier verschijnt de goedgekeurde stap voor deze handeling.', 'help' => 'Hier is plek voor extra uitleg over deze stap.'],
                ['id' => 'finish', 'title' => 'Ga naar de eindcheck', 'body' => 'Bekijk hoe je een uitvoering in de demo kunt vastleggen.', 'help' => 'De eindcheck legt je eigen terugmelding vast.'],
            ],
            'questions' => [
                ['id' => 'doubt', 'label' => 'Was er iets waarover je twijfelde in deze demo?',
                    'options' => ['no' => 'Nee', 'yes' => 'Ja', 'unknown' => 'Weet ik niet']],
            ],
        ];
    }
}
