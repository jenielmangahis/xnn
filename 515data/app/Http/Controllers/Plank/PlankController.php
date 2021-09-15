<?php

namespace App\Http\Controllers\Plank;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\EnergyAccount;
use App\EnergyAccountLog;
use App\EnergyType;
use App\EnergyAccountType;
use App\EnergyAccountStatusType;
use App\User;
use App\CategoryMap;
use Exception;
use DB;

class PlankController extends Controller
{
	//6a7c440e-7c9d-40f4-ac56-9789a0f7901b
	static $_ACCOUNT_TYPES = ['Residential', 'Commercial'];
	static $_ENERGY_TYPES = ['Gas', 'Electric'];
	static $_STATUS_TYPES = ['Pending Signature', 'Pending Accepted', 'Pending Rejection', 'Accepted Pending Flowing',
							'Flowing and Paying', 'Flowing, Not Paying', 'Flowing Pending Cancellation and Paying', 'Flowing Pending Cancellation Not Paying',
							'Cancelled'];

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

		if ($update) {
			if (!$request->has('customerId')) {
				return Response::BadRequest("Missing Parameter", "customerId is missing.");
			}
		}

		if (!$request->has('sponsorId')) {
			return Response::BadRequest("Missing Parameter", "sponsorId is missing.");
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

		//check if values are in list
		$energyType = EnergyType::find($request->energyType);
		if (!$energyType || empty($energyType)) {
			return Response::BadRequest("Unrecognized Parameter Value", "energyType is unrecognized.");
		}

		$accountType = EnergyAccountType::find($request->accountType);
		if (!$accountType || empty($accountType)) {
			return Response::BadRequest("Unrecognized Parameter Value", "accountType is unrecognized.");
		}

		$status = EnergyAccountStatusType::find($request->status);
		if (!$status || empty($status)) {
			return Response::BadRequest("Unrecognized Parameter Value", "status is unrecognized.");
		}
	}

    public function post(Request $request)
    {
		$log = new EnergyAccountLog();
		$log->request_type = 'INSERT';
		$log->ip_address = $request->ip();
		$log->request_body = $request->getContent();
		$log->reference_id = $request->referenceId;	//Energy POD or Gas PDR

		try 
		{
			$valid = $this->validateRequest($request, false);
			if (isset($valid) || !empty($valid)) {
				return $valid;
			}

			$user = new User();
			$user->fname = $request->firstname;
			$user->lname = $request->lastname;
			$user->email = $request->email;
			$user->sponsorid = $request->sponsorId;
			$user->levelid = 3;
			$user->active = 'Yes';
			$user->site = null;
			$user->password = null;
			$user->created = Carbon::now()->timestamp;
			$user->save();

			$catmap = new CategoryMap();
			$catmap->catid = 13;
			$catmap->userid = $user->id;

			$account = new EnergyAccount();
			$account->reference_id = $request->referenceId;
			$account->customer_id = $user->id;
			$account->sponsor_id = $request->sponsorId;
			$account->account_type = $request->accountType;
			$account->energy_type = $request->energyType;
			$account->status = $request->status;

			$account->save();
	
			//do save here 
			$log->notes = $request->has('tioat') ? "tioatvsy" : "Record Saved";
			$log->old_status = $request->status;
			$log->current_status = $request->status;
			$log->customer_id = $user->id;
		}
		catch (QueryException $e)
		{
			if ($e->errorInfo[1] == 1062) {
				$log->notes = $request->has('tioat') ? "tioatvsy ". $e->getMessage() : "Record Not Saved". $e->getMessage();
				return Response::ServerError("Cannot have duplicate entry.");
			}
			return Response::ServerError($e->getMessage());
		}
		catch (Exception $e)
		{
			$log->notes = $request->has('tioat') ? "tioatvsy ". $e->getMessage() : "Record Not Saved". $e->getMessage();
			return Response::ServerError("Exception: " . $e->getMessage());
		}
		finally
		{
			$log->save();
		}

		return Response::Success("New Record Saved.", ['id' => $user->id]);
    }

    public function put(Request $request)
    {
		$log = new EnergyAccountLog();
		$log->request_type = 'UPDATE';
		$log->ip_address = $request->ip();
		$log->request_body = $request->getContent();
		$log->reference_id = $request->reference_id;

		try 
		{
			$valid = $this->validateRequest($request);
			if (isset($valid) || !empty($valid)) {
				return $valid;
			}

			//old account
			$referenceId = $request->referenceId;

			$oldAccount = EnergyAccount::where('reference_id', $referenceId)->first();

			if (!isset($oldAccount)) {
				return Response::BadRequest("Data does not exist.", "Reference id $referenceId does not exist.");
			}

			EnergyAccount::where('reference_id', $referenceId)
							->update(
								[	'reference_id' => $referenceId, 
									//'customer_id' => $request->customerId,
									'sponsor_id' => $request->sponsorId,
									'account_type' => $request->accountType,
									'energy_type' => $request->energyType,
									'status' => $request->status
								]);
	
			//do save here
			$log->notes = $request->has('tioat') ? "tioatvsy" : "Record Updated";
			$log->old_status = $oldAccount->status;
			$log->current_status = $request->status;
		} 
		catch (QueryException $e)
		{
			if ($e->errorInfo[1] == 1062) {
				$log->notes = $request->has('tioat') ? "tioatvsy ". $e->getMessage() : "Record Not Updated". $e->getMessage();
				return Response::ServerError("Cannot have duplicate entry.");
			}
			return Response::ServerError($e->getMessage());
		}
		catch (Exception $e)
		{
			$log->notes = $request->has('tioat') ? "tioatvsy ". $e->getMessage() : "Record Not Updated". $e->getMessage();
			return Response::ServerError("Exception: " . $e->getMessage());
		}
		finally
		{
			$log->save();
		}

		return Response::Success("Record Updated.");
	}
	
	private function date($dateString)
	{
		return (bool)strtotime($dateString);
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
