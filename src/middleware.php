<?php

use Adesa\SmartLabelClient\SmartLabel;
use Symfony\Component\HttpFoundation\Request;


/**
 * @var $app \Silex\Application
 */

$app->before(function (Request $request, $app) {
        $acceptLanguageHeaderValue = $request->headers->get("Accept-Language");
        $locales = explode(',', $acceptLanguageHeaderValue);
        // todo language negotiation
        $localeFromHeader = $locales[0];
        $locale = $request->query->get("locale", $localeFromHeader);
        return $locale;
});







