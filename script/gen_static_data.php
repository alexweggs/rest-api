<?php

use Adesa\SmartLabelClient\Config;
use Adesa\SmartLabelClient\SmartLabel;

require '../vendor/autoload.php';

$client = new SmartLabel(Config::fromIniFile(__DIR__ . '/../config.ini'));

$data = [];

/*
 *  MATERIALS -> FINISHES -> SCENARIOS
 */
$matieres = $client->listeMatieres();
$finishes = [];

foreach($matieres as $matiere){
    $material = [
        "id" => $matiere->numero,
        "name" => $client->label($matiere),
        "finishes" => [],
    ];

    foreach($client->listeFinitions($matiere) as $finition){

        $material["finishes"][] = $finition->numero;

        $finishes[$finition->numero] =  [
            "id" => $finition->numero,
            "name" => $client->label($finition)
        ];

        $scenario = $client->trouverScenario($matiere, $finition);

        $data['scenarios'][] = [
            "id" => $scenario->numero,
            "finish" => $finition->numero,
            "material"=> $matiere->numero,
            "name" => $scenario->nom,
            "leadTime" => $scenario->delai,
        ];
    }

    $data['materials'][] = $material;
    $data['finishes'] = array_values($finishes);

}

/*
 *  CORE SIZES
 */
$cores = $client->listeMandrins();
foreach ($cores as $core){
    $data['cores'][] = [
        'size' => $core->diametre,
        'name' => $client->label($core)
    ];
}

/*
 *  CUTTINGS
 */
$data['cuttings'] = [
    [
        "label" => "Cut to shape",
        "value" => "FORME"
    ],
    [
        "label" => "Straight cut",
        "value" => "DROITE"
    ]
];

/*
 *  ORIENTATIONS
 */
$data['orientations'] = [0, 90, 180, 270];

/*
 * APPLICATION
 */
$data['applications'] = ['manual', 'automatic'];

/*
 * WRITING IT DOWN...
 */
file_put_contents(__DIR__ . '/static.json', json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));