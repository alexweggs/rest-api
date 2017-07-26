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
        ($params->get('application', 'manual') == 'automatic'),
        new \Adesa\SmartLabelClient\Mandrin($params->get('core_size')),
        $params->get('nb_labels_per_reel'),
        $params->get('nb_reels'),
        $params->get('orientation'),
        $params->get('nb_labels_per_versions')
    );

    return $app->json([
        'diameter' => $dossier->diametre,
        'price' => $dossier->prix,
        'job_id' => $dossier->numero,
        'weight' => $dossier->poids
    ]);
});


$app->post('/v1/order', function (Application $app, Request $request) use ($smartLabel) {
    $filesToDelete = [];
    $uploadDir = __DIR__ . '/../uploads';

    $params = $request->request;
    $dossier = new \Adesa\SmartLabelClient\Dossier();
    $dossier->numero = $params->get('job_id');
    $dossier->scenario = new \Adesa\SmartLabelClient\Scenario($params->get('scenario_id'));
    $dossier->quantitesParSerie = implode(';', $params->get('nb_labels_per_versions'));

    $bdc = $smartLabel->commander($dossier, $params->get('external_order_id'));

    $versionsTitles = $params->get('versions_titles');


    $smartLabel->creerAdresseLivraison(
        $dossier,
        $params->get('address_full_name'),
        $params->get('address_street_address'),
        $params->get('address_postal_code'),
        $params->get('address_city'),
        $params->get('address_country')
    );
    /**
     * @var $version Symfony\Component\HttpFoundation\File\UploadedFile
     */
    foreach ($request->files->get('versions_files') as $i => $version) {
        $filename = uniqid('upload') . '.' . $version->getClientOriginalExtension();
        $version->move($uploadDir, $filename);
        $bdc->ajouterModele($versionsTitles[$i], $uploadDir . '/' . $filename);
        $filesToDelete[] = $uploadDir . '/' . $filename;
    }

    $smartLabel->finaliserBonDeCommande($bdc);

    foreach ($filesToDelete as $file) {
        unlink($file);
    }

    return $app->json([
        "order_reference" => $bdc->reference,
        "job_id" => $bdc->dossier->numero
    ]);
});

$app->get('/v1/status', function (Application $app, Request $request) use ($smartLabel) {
    $params = $request->request;
    $ids = $params->get("job_ids");
    $status = $smartLabel->etatDossiers(is_array($ids) ? $ids : explode(";", $ids));

    return $app->json(array_map(function (\Adesa\SmartLabelClient\EtatDossier $item) {
        return [
            "job_id" => $item->numero,
            "code" => $item->code,
            "infos" => $item->informationsLivraison,
            "tracking_url" => $item->trackingURL
        ];
    }, $status));
});