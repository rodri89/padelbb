<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\ConsumesExternalServices;
use App\Services\CurrencyConversionService;

class MercadoPagoService
{

	use ConsumesExternalServices;

	protected $baseUri;

	protected $key;

	protected $secret;

	protected $baseCurrency;

	protected $value;

	protected $converter;

	public function __construct(CurrencyConversionService $converter, $key, $secret, $value)
	{
		$this->baseUri = 'https://api.mercadopago.com';
		$this->key = $key;//'TEST-f050a5c4-7157-4cf3-8e63-8682f912d564';
		$this->secret = $secret;//'TEST-104399887932260-033121-40b8ed55cfc0f16da09e4e99c310d6ca-206496366';
		$this->baseCurrency = 'ars';

		$this->value = $value;
		$this->converter = $converter;
	}

	public function resolveAuthorization(&$queryParams, &$formParams, &$headers) {
		$queryParams['access_token'] = $this->resolveAccessToken();
	}

	public function decodeResponse($response) {
		return json_decode($response);
	}

	public function resolveAccessToken() {
		return $this->secret;
	}

	public function handlePayment(Request $request) {			
		$payment = $this->createPayment(
					$this->value,
					'ars',
					$request->card_network,
					$request->card_token,
					$request->email
					);
		return $payment;
		/*if($payment->status === "approved") {
			$name = $payment->payer->first_name;
			$amount = number_format($payment->transaction_amount, 0, ',', '.');			
			return $payment;//'su pago de '.$amount.' ha sido aprobado';
		} else {
			return $payment->status_detail;
		}*/
	}

	public function handleApproval() {
		//mercado pago no requiere este metodo.
	}

	// $installments es cantidad de cuotas
    public function createPayment($value, $currency, $cardNetwork, $cardToken, $email, $installments = 1)
    {
        return $this->makeRequest(
            'POST',
            '/v1/payments',
            [],
            [
                'payer' => [
                    'email' => $email,
                ],
                'binary_mode' => true,
                'transaction_amount' => round($value * $this->resolveFactor($currency)),
                'payment_method_id' => $cardNetwork,
                'token' => $cardToken,
                'installments' => $installments,
                'statement_descriptor' => 'turnos_online_bb',
            ],
            [],
            true
        );
    }

	public function resolveFactor($currency) {
		return $this->converter->convertCurrency($currency, $this->baseCurrency);
	}

}

?>
