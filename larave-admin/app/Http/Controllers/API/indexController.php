<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Elasticsearch\ClientBuilder;

class indexController extends Controller {
    public $client = null;

    public function __construct() {
        $this-> client = ClientBuilder::create()->build();
    }

    //创建
    public function index(){
        $params = [
            'index' => 'studb',
            'type' => 'teacher',
            'id' => 't_id',
            'body' => ['1' => 'abc']
        ];

        $response = $this->client->index($params);
        print_r($response);
    }

}