<?php

namespace App\Http\Controllers\API\Panel\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\VisitInterface;

class VisitsController extends Controller
{
    public $IVisit;

    public function __construct(VisitInterface $IVisit)
    {
        $this->IVisit = $IVisit;
    }

    public function index(Request $request)
    {
        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->IVisit->getVisitsForManager($request, $subordinateIds);

        return $this->SendResponse($response);
    }

   
    public function show($id)
    {
        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->IVisit->showVisitForManager($id, $subordinateIds);

        return $this->SendResponse($response);
    }
}