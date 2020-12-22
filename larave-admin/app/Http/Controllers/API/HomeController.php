<?php
namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Elasticsearch\ClientBuilder;

class HomeController extends Controller {
    public $client = null;

    public function __construct() {
        $this-> client = ClientBuilder::create()->build();
    }



  //创建
    public function getDocument()
	{
		$client =ClientBuilder::create()->build();
        $params = [
            'index' => 'studb',
            'type' => 'teacher',
            'id' => 't_id',
            
			];

        $response = $client->get($params);
        print_r($response);
    }




  
}