<?php

use Adesa\SmartLabelClient\SmartLabel;

if (PHP_SAPI == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';
session_start();

/**
 * @SWG\Swagger(
 *     schemes={"http"},
 *     host="api.smartlabel.fr",
 *     basePath="/v1",
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="Adesa SmartLabel API",
 *         description="",
 *         @SWG\Contact(
 *             email="marketing@smartlabel.fr"
 *         )
 *     )
 * )
 */

$app = new Silex\Application();
$app['debug'] = true;
$app['smartLabel'] = new SmartLabel(\Adesa\SmartLabelClient\Config::fromIniFile(__DIR__ . '/../config.ini'));

require __DIR__ . '/../src/middleware.php';
require __DIR__ . '/../src/routes.php';
$app->run();