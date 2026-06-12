<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;

class CurrencyConversionService
{
    use ConsumesExternalServices;

    protected $baseUri;

    protected $apiKey;

    public function __construct()
    {
        $this->baseUri = 'https://free.currconv.com'; // esta es el servicio que vamos a usar...pagina = https://free.currencyconverterapi.com/
        $this->apiKey = '5f371c8c36abf29c3b68'; // esta key me llego por mail cuando me registre en la pagina.
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        $queryParams['apiKey'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken()
    {
        return $this->apiKey;
    }

    public function convertCurrency($from, $to)
    {
        $response = $this->makeRequest(
            'GET',
            '/api/v7/convert',
            [
                'q' => "{$from}_{$to}",
                'compact' => 'ultra',
            ]
        );

        return $response->{strtoupper("{$from}_{$to}")};
    }

}