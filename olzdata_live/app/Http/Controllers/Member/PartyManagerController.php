<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Member\PartyManager;
use Illuminate\Support\Facades\Auth;

class PartyManagerController extends Controller
{
    protected $party_manager;

    public function __construct(PartyManager $party_manager)
    {
        $this->party_manager = $party_manager;
    }

    public function getOpenEvents(Request $request)
    {
        return response()->json(
            $this->party_manager->getOpenEvents($request->all(), Auth::user()->id)
        );
    }
    
    public function getPastEvents(Request $request)
    {
        return response()->json(
            $this->party_manager->getPastEvents($request->all(), Auth::user()->id)
        );
    }
    
    public function getTopHostesses(Request $request)
    {
        return response()->json(
            $this->party_manager->getTopHostesses($request->all(), Auth::user()->id)
        );
    }

    public function getOrders($id)
    {
        return response()->json(
            $this->party_manager->getOrders($id)
        );
    }

    public function createEvent(Request $request)
    {
        return response()->json(
            $this->party_manager->create($request->all(), Auth::user()->id)
        );
    }

    public function deleteEvent($id)
    {
        return response()->json(
            $this->party_manager->delete($id)
        );
    }

}
