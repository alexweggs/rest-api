<?php

use Silex\Application;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;


/** @var $smartLabel \Adesa\SmartLabelClient\SmartLabel */
$smartLabel = $app['smartLabel'];

function getQuantitesParSerieParamFromVersions($versions)
{
    $quantities = [];
    foreach ($versions as $version) {
        $quantities[] = $version->quantity;
    }
    return implode(';', $quantities);
}

$app->get('/v1/parameters', function (Application $app) use ($smartLabel) {
    return $app->sendFile(__DIR__ . '/../script/static.json', 200, [
        'Content-Type' => 'application/json;charset=UTF-8'
    ]);
});


$app->post('/v1/quote', function (Application $app, Request $request) use ($smartLabel) {
    $params = $request->request;
    $dossier = $smartLabel->demandePrix(
        new \Adesa\SmartLabelClient\Scenario($params->get('scenario')),
        $params->get('quantity'),
        $params->get('height'),
        $params->get('width'),
        $params->get('application') == 'automatic',
        $params->get('core_size'),
        $params->get('nb_labels_per_reel'),
        $params->get('nb_reels'),
        $params->get('orientation'),
        $params->get('nb_labels_per_versions')
    );

    return $app->json([
        'diameter' => $dossier->diametre,
        'price' => $dossier->prix,
        'quote_id' => $dossier->numero,
        'weight' => $dossier->poids
    ]);
});


$app->post('/v1/order', function (Application $app, Request $request) use ($smartLabel) {
    $params = $request->request;

    $dossier = new \Adesa\SmartLabelClient\Dossier();
    $dossier->numero = $params->get('quote_id');
    $dossier->quantitesParSerie = $params->get('nb_labels_per_versions');

    $bdc = $smartLabel->commander($dossier, $params->get('external_order_id'));

    $versionsTitles = $params->get('versions_titles');

    foreach ($params->get('versions_files') as $i => $version) {
        $bdc->ajouterModele($versionsTitles[$i], $params->get("versions_files"));
    }

    $smartLabel->finaliserBonDeCommande($bdc);

    return $app->json([
        "order_reference" => $bdc->reference,
        "quote_id" => $bdc->dossier->numero
    ]);
});

$app->get('/v1/status', function(Application $app, Request $request) use ($smartLabel) {
   $params = $request->request;
    $ids = $params->get("quote_ids");
    $status = $smartLabel->etatDossiers(is_array($ids) ? $ids : explode(";", $ids));

    return $app->json(array_map(function(\Adesa\SmartLabelClient\EtatDossier $item){
        return [
            "quote_id" => $item->numero,
            "code" => $item->code,
            "infos" => $item->informationsLivraison,
            "tracking_url" => $item->trackingURL
        ];
    }, $status));
});