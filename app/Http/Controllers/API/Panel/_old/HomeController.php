<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\HomeInterface;

class HomeController extends Controller
{

	public $IHome;
    public function __construct(HomeInterface $IHome)
    {
        $this->IHome = $IHome;
    }

	public function index(Request $request)
	{
		$response = $this->IHome->getAll();
		return $this->SendResponse($response);
	}


	public function getLogs(){
		$response = $this->IHome->getAllLogs();
		return $this->SendResponse($response);
	}

}