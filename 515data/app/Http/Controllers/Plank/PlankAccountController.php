<?php

namespace App\Http\Controllers\Plank;

use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\EnergyAccount;
use App\EnergyAccountLog;
use App\EnergyType;
use App\EnergyAccountType;
use App\EnergyAccountStatusType;
use App\EnergyAccountStatusTypeDetail;
use App\EnergyAccountStatusLog;
use App\EnergyAccountFlowing;
use App\EnergyAccountCancellation;
use App\EnergyAccountApiLog;
use App\User;
use App\CommissionPayout;
use App\CategoryMap;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use DateTime;

class PlankAccountController extends Controller
{
	//6a7c440e-7c9d-40f4-ac56-9789a0f7901b
	static $_ACCOUNT_TYPES = ['Residential', 'Commercial'];
	static $_ENERGY_TYPES = ['Gas', 'Electric'];
	static $_STATUS_TYPES = ['Pending Signature', 'Pending Accepted', 'Pending Rejection', 'Accepted Pending Flowing',
							'Flowing and Paying', 'Flowing, Not Paying', 'Flowing Pending Cancellation and Paying', 'Flowing Pending Cancellation Not Paying',
							'Cancelled'];

	
	private function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}

	public function getAll(Request $request)
	{
		$token = $request->bearerToken();
		
		if (empty($token) || $token != config('commission.plank_api_token')) {
			return Response::Unauthorized();
		}
		
        return Response::Success("", EnergyAccount::all());
	}

    public function get(Request $request)
    {
		$token = $request->bearerToken();
		
		if (empty($token) || $token != config('commission.plank_api_token')) {
			return Response::Unauthorized();
		}

		$referenceId = $request->route('referenceId');

		if (isset($referenceId) && !empty($referenceId)) {
			$account = EnergyAccount::where('reference_id', $referenceId)->get();
			if ($account) {
				return Response::Success("", $account);
			} else {
				return Response::Success("Account not found.");
			}
		}

        return Response::Success("", EnergyAccount::all());
	}
	
	private function validateRequest(Request $request, $update = true) 
	{
		$token = $request->bearerToken();
		
		if (empty($token) || $token != config('commission.plank_api_token')) {
			return Response::Unauthorized();
		}

		//check all empty params first
		if (!$request->has('referenceId')) {
			return Response::BadRequest("Missing Parameter", "referenceId is missing.");
		}

		if (!$request->has('customerId')) {
			return Response::BadRequest("Missing Parameter", "customerId is missing.");
		}

		if (!$request->has('plankEnergyAccountId')) {
			return Response::BadRequest("Missing Parameter", "plankEnergyAccountId is missing.");
		}

		if (!$request->has('associateId')) {
			return Response::BadRequest("Missing Parameter", "associateId is missing.");
		}

		if (!$request->has('status')) {
			return Response::BadRequest("Missing Parameter", "status is missing.");
		}

		if (!$request->has('accountType')) {
			return Response::BadRequest("Missing Parameter", "accountType is missing.");
		}

		if (!$request->has('energyType')) {
			return Response::BadRequest("Missing Parameter", "energyType is missing.");
		}

		if ($request->has('date_starts_flowing')) {
			if (!$this->validateDate($request->date_starts_flowing, 'd-m-Y')) {
				return Response::BadRequest("Invalid Date Format", "Please follow dd-mm-yyyy format.");
			}
		}

		if ($request->has('date_stops_flowing')) {
			if (!$this->validateDate($request->date_stops_flowing, 'd-m-Y')) {
				return Response::BadRequest("Invalid Date Format", "Please follow dd-mm-yyyy format.");
			}
		}

		/*
		if ($request->status == 5) {
			if (!$request->has('date_starts_flowing')) {
				return Response::BadRequest("Missing Parameter", "date_starts_flowing is missing.");
			}
		}

		if ($request->status == 7) {
			if (!$request->has('date_stops_flowing')) {
				return Response::BadRequest("Missing Parameter", "date_stops_flowing is missing.");
			}
		}
		*/

		//check if values are in list
		$energyType = EnergyType::find($request->energyType);
		if (!$energyType || empty($energyType)) {
			return Response::BadRequest("Unrecognized Parameter Value", "energyType is unrecognized.");
		}

		$accountType = EnergyAccountType::find($request->accountType);
		if (!$accountType || empty($accountType)) {
			return Response::BadRequest("Unrecognized Parameter Value", "accountType is unrecognized.");
		}

		$status = EnergyAccountStatusTypeDetail::where('type', $request->status)->first();
		if (!$status || empty($status)) {
			return Response::BadRequest("Unrecognized Parameter Value", "status is unrecognized.");
		}
	}

	private function cleanAssociateID($associateID) {
		if (!empty($associateID)) {
			if (strtolower($associateID[0]) == 'n') {
				return substr($associateID, 1);
			} else if (is_numeric($associateID)){
				return $associateID;
			}
		}

		return false;
	}

    public function post(Request $request)
    {
		try 
		{
			$valid = $this->validateRequest($request, false);
			if (isset($valid) || !empty($valid)) {
				return $valid;
			}

			$associateID = $this->cleanAssociateID($request->associateId);
	
			if (!$associateID) {
				return Response::BadRequest("Invalid Parameter Value", "associateId value provided is invalid.");
			}

			$exists = EnergyAccount::where('plank_energy_account_id', $request->plankEnergyAccountId)->first();

			if (!empty($exists)) {
				return Response::ServerError("Cannot have duplicate entry with the same plankEnergyAccountId. Try PUT instead.");
			}

			$exists = Customer::where('memberid', $request->customerId)->first();

			$user = new Customer();

			if (empty($exists)) {
				//do not insert new record in users table, instead do update
				if ($request->has('firstname')) {
					$user->fname = $request->firstname;
				}
				if ($request->has('lastname')) {
					$user->lname = $request->lastname;
				}
				if ($request->has('email')) {
					$user->email = $request->email;

                    $prospect = User::where('levelid', 4)->where('email', $user->email)->first();
                    if($prospect !== null) $prospect->delete();

				}
				if ($request->has('company_name')) {
					$user->business = $request->company_name;
				}
				$user->memberid = $request->customerId;
				$user->sponsorid = $associateID;
				$user->levelid = 3;
				$user->active = 'Yes';
				$user->site = null;
				$user->password = null; 
				$user->created = date('Y-m-d H:i:s');
				$user->save();
	
				/*$catmap = new CategoryMap();
				$catmap->catid = 13;
				$catmap->userid = $user->id;
				$catmap->save();*/
			}

			//check request status
			$requestParentStatus = EnergyAccountStatusTypeDetail::where('type', $request->status)->first();

			$requestParentStatus = $requestParentStatus->parent_status_type;
	
			$account = new EnergyAccount();
			$account->reference_id = $request->referenceId;
			$account->customer_id = empty($exists) ? $user->id: $exists->id;
			$account->plank_energy_account_id = $request->plankEnergyAccountId;
			$account->sponsor_id = $associateID;
			$account->account_type = $request->accountType;
			$account->energy_type = $request->energyType;
			$account->status = $requestParentStatus;

			$account->save();

			
			$this->createAccountStatusChangeLog($request, $requestParentStatus, $account->id, $account->customer_id, $request->input('date_starts_flowing'));

			$this->createAccountStatusLog($request);

			$this->doCancellationOrFlowingLog($request);
		}
		catch (QueryException $e)
		{
			if ($e->errorInfo[1] == 1062) {
				return Response::ServerError("Cannot have duplicate entry.");
			}
			return Response::ServerError($e->getMessage());
		}
		catch (Exception $e)
		{
			return Response::ServerError("Exception: " . $e->getMessage());
		}

		return Response::Success("New Record Saved.");
	}
	
	public function put(Request $request)
	{
		try 
		{
			$this->createApiRequestLog($request);

			$valid = $this->validateRequest($request);
			if (isset($valid) || !empty($valid)) {
				return $valid;
			}

			$plankEnergyAccountId = $request->plankEnergyAccountId;

			$oldAccount = EnergyAccount::where('plank_energy_account_id', $plankEnergyAccountId)->first();

			if (empty($oldAccount)) {	//create a new record
				return $this->post($request);

			} else {

				$associateID = $this->cleanAssociateID($request->associateId);

				if (!$associateID) {
					return Response::BadRequest("Invalid Parameter Value", "associateId value provided is invalid.");
				}

				//check current status
				$currentAccountStatusType = $oldAccount->status;

				//check request status
				$requestParentStatus = EnergyAccountStatusTypeDetail::where('type', $request->status)->first();

				$requestParentStatus = $requestParentStatus->parent_status_type;

//				if ($currentAccountStatusType != $requestParentStatus) {
//					//this is a different status, create record in energy account log
//
//				}
                $this->createAccountStatusChangeLog($request, $requestParentStatus, $oldAccount->id, $oldAccount->customer_id, $request->input('date_starts_flowing'));

				/*
					3) In setting the energy account's current status, follow the latest status received from Plank. 
					Except when the status received is Approved, Pending Flowing and it has an existing Flowing status in the history, the latest status should be Flowing.
				*/
                $flowingStatusExists =  EnergyAccountLog::where('current_status', 5)->where('energy_account_id', $oldAccount->id)->first();
				$highest_status = $requestParentStatus;

                if($requestParentStatus == 4 && $flowingStatusExists !== null)
                {
                    $highest_status = 5;
                }

				EnergyAccount::where('plank_energy_account_id', $plankEnergyAccountId)
				->update(
					[	'sponsor_id' => $associateID,
						'account_type' => $request->accountType,
						'energy_type' => $request->energyType,
						'status' => $highest_status
					]);

				$this->createAccountStatusLog($request);

				$this->doCancellationOrFlowingLog($request);
			}
		} 
		catch (QueryException $e)
		{
			if ($e->errorInfo[1] == 1062) {
				return Response::ServerError("Cannot have duplicate entry.");
			}
			return Response::ServerError($e->getMessage());
		}
		catch (Exception $e)
		{
			return Response::ServerError("Exception: " . $e->getMessage());
		}

		return Response::Success("Record Updated.");
	}

	private function createAccountStatusLog($obj)
	{
		$log = new EnergyAccountStatusLog();
		$log->status_type = $obj->status;
		$log->plank_energy_account_id = $obj->plankEnergyAccountId;
		$log->save();
	}

	private function createAccountStatusChangeLog($obj, $newStatus, $energyAccountId, $customerid, $date_starts_flowing = null)
	{
        $flowing_status = EnergyAccountLog::where('current_status', 5)->where('energy_account_id', $energyAccountId)->first();
		/*
			2) If the date_starts_flowing has value but the status is not Approved, Pending Flowing, do not record the date_starts_flowing as the Flowing date.
			//Rephrased
			2) If the date_starts_flowing has value and the status is Approved, Pending Flowing, record the date_starts_flowing as the Flowing date.
		*/
        if(($date_starts_flowing !== null) && empty($flowing_status) && ($newStatus == 4)) {
            $date_starts_flowing = Carbon::createFromFormat('d-m-Y', $date_starts_flowing)->startOfDay();
            if($date_starts_flowing->lessThan(Carbon::now())) {
                $log = new EnergyAccountLog();
                $log->customer_id = $customerid;
                $log->energy_account_id = $energyAccountId;
                $log->reference_id = $obj->referenceId;
                $log->current_status = 5;
                $log->created_at = $date_starts_flowing;
                $log->save();
            }
        }

        $approved_pending_flowing_status = config('commission.energy-account-status-types.approved-pending-flowing');
		/*
			1) If status = Approved, Pending Flowing:
		*/
        if($newStatus == +$approved_pending_flowing_status) {
			/*
				If it was NEVER paid in Immediate Earnings (POD/PDR as the basis):
				Update the Approved date to the current date so it gets paid out
			    Update 2021-07-19 - New query for checking if the reference id is paid in immediate earnings
			*/
            $is_paid = DB::table('cm_commission_payouts AS ccp')
                ->join('cm_energy_accounts AS cea', 'cea.id', '=', 'ccp.transaction_id')
                ->join('cm_commission_periods AS cp', 'cp.id', '=', 'ccp.commission_period_id')
                ->where('cp.is_locked', 1)
                ->where('cp.commission_type_id', 1)
                ->where('cea.reference_id', $obj->referenceId)
                ->get();

            if($is_paid == null) {
                $log = new EnergyAccountLog();
                $log->customer_id = $customerid;
                $log->energy_account_id = $energyAccountId;
                $log->reference_id = $obj->referenceId;
                $log->current_status = +$approved_pending_flowing_status;
                $log->created_at = Carbon::now();
                $log->save();
            }
        }

        /*
            Remove the Canceled date from history (if exists) and if the new status is not canceled status(7)
        */
        if($newStatus !== 7){
            $canceled_history = EnergyAccountLog::where('energy_account_id', $energyAccountId)->where('current_status', 7)->first();
            if($canceled_history !== null) $canceled_history->delete();
        }

        $exists = EnergyAccountLog::where('current_status', $newStatus)->where('energy_account_id', $energyAccountId)->first();

        if($exists !== null) return;

		$log = new EnergyAccountLog();
		$log->customer_id = $customerid;
		$log->energy_account_id = $energyAccountId;
		$log->reference_id = $obj->referenceId;
		$log->current_status = $newStatus;

		$log->created_at = Carbon::now();

		$log->save();
	}

	private function createApiRequestLog($obj)
	{
		$log = new EnergyAccountApiLog();
		$log->ip_address = $obj->ip();
		$log->request_body = $obj->getContent();
		$log->save();
	}

	private function getCurrentStatus($plankEnergyAccountId)
	{
		return EnergyAccountStatusLog::where('plank_energy_account_id', $plankEnergyAccountId)->latest()->first();
	}

	private function doCancellationOrFlowingLog($obj)
	{
		if ($obj->has('date_starts_flowing')) {
            //check request status
            $requestParentStatus = EnergyAccountStatusTypeDetail::where('type', $obj->status)->first();

            $requestParentStatus = $requestParentStatus->parent_status_type;
		    if($requestParentStatus !== 4) return;

			//do flowing stuff
			//check if record already exists
			$d = DateTime::createFromFormat('d-m-Y', $obj->date_starts_flowing);
			$date_starts_flowing = $d->format('Y-m-d');

			$exists = EnergyAccountFlowing::where('plank_energy_account_id', $obj->plankEnergyAccountId)->latest()->first();

			if (empty($exists)) {
				$model = new EnergyAccountFlowing();
				$model->plank_energy_account_id = $obj->plankEnergyAccountId;
				$model->flowing_date = $date_starts_flowing;
				$model->save();
			} else {
                $exists->flowing_date = $date_starts_flowing;
                $exists->save();
            }
		}

		if ($obj->has('date_stops_flowing')) {
			//do cancellation stuff
			//check if record already exists
			$d = DateTime::createFromFormat('d-m-Y', $obj->date_stops_flowing);
			$date_stops_flowing = $d->format('Y-m-d');

			$exists = EnergyAccountCancellation::where('plank_energy_account_id', $obj->plankEnergyAccountId)->latest()->first();

			if (empty($exists)) {
				$model = new EnergyAccountCancellation();
				$model->plank_energy_account_id = $obj->plankEnergyAccountId;
				$model->cancellation_date = $date_stops_flowing;
				$model->save();
			} else {
                $exists->cancellation_date = $date_stops_flowing;
                $exists->save();
            }
		}
	}
}

class Response {

	static function Unauthorized() {
		return response()->json(
			array(	'error' => true, 
					'message' => 'Unauthorized Request.', 
					'details' => 'Invalid Token.')
				, 401);
	}

	static function Success($message = "", $data = null) {
		if ($data) {
			return response()->json(
				array(	'success' => true, 
						'data' => $data)
					, 200);
		}

		return response()->json(
			array(	'success' => true, 
					'message' => 'Success.', 
					'details' => $message)
				, 200);
	}

	static function ServerError($message) {
		return response()->json(
			['data' => 
				array(	'error' => true, 
						'message' => 'Server Error.', 
						'details' => $message)
			], 500);
	}

	static function BadRequest($message, $details) {
		return response()->json(
			['data' => 
				array(	'error' => true, 
						'message' => $message, 
						'details' => $details)
			], 400);
	}

}
