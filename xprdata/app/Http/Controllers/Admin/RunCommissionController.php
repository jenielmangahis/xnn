<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers\Admin;

use Commissions\Admin\RunCommission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RunCommissionController extends Controller
{
    protected $run_commission;
    
    public function __construct(){
        $this->run_commission = new RunCommission();
    }

    public function run($id)
    {
        return response()->json(
            $this->run_commission->run($id)
        );
    }

    public function lock($id)
    {
        return response()->json(
            $this->run_commission->lock($id)
        );
    }

    public function getPreviousRun($id)
    {
        return response()->json(
            $this->run_commission->getPreviousRun($id)
        );
    }

    public function complete($id)
    {
        return response()->json(
            $this->run_commission->complete($id)
        );
    }

    public function cancel($id)
    {
        return response()->json(
            $this->run_commission->cancel($id)
        );
    }

    public function log($id, Request $request)
    {
        return response()->json(
            $this->run_commission->log($id, $request->input('seek'))
        );
    }

    public function details($id)
    {
        return response()->json(
            $this->run_commission->details($id)
        );
    }
}
