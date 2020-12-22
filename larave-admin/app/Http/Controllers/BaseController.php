<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    public function __construct() {
        $this->beforeFilter(function() {
            $this->before();
        });
    }
    
    protected function before() {
    }
}

?>