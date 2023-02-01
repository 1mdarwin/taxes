<?php

namespace Drupal\taxes;

use Drupal\Component\Serialization\Json;

class ImpuestosClient {
	/**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * ImpuestosClient constructor
   *
   * @param $http_client_factory \Drupal\Core\Http
   */
  public function __construct($http_client_factory){
    $this->client = $http_client_factory->fromOptions([
      // 'base_uri' => 'http://192.168.1.146:8080',
      'base_uri' => 'http://'. URI_GIM .':8080',
    ]);
  }

  /**
   * Consume endpoint webservice using authenticacion and ID customer (cedula)
   * @param $cedula
   */
  public function withCedula($cedula = '' ){

    // $cedula = '1104262835';
    // $username = 'dabetancourtc';

    $response = $this->client->post('/gim/seam/resource/rest/queries/debts', [
        'auth' => [USER_GIM, PASS_GIM],
        'json' => [
          'identification' => $cedula,
        ],
        // 'debug' => true,
    ]);

    $data = Json::decode($response->getBody());
    // if ($amount == 1){
    //   $data = [$data];
    // }
    // dpm($data);

    return $data;

  }
}
