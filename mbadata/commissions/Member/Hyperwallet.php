<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 11/13/2019
 * Time: 9:32 AM
 */

namespace Commissions\Member;

use Hyperwallet\Hyperwallet as HW;
use Illuminate\Support\Facades\DB;
use Commissions\Exceptions\HyperwalletException;
use App\HyperwalletUser;

class Hyperwallet
{
    const DEBUG = false;

    protected $hyperwallet;

    public function __construct(\Hyperwallet\Hyperwallet $hyperwallet)
    {
        $this->hyperwallet = $hyperwallet;
    }

    public function createUser($id, $data)
    {
        if(self::DEBUG) {
            $data['client_user_id'] = $data['client_user_id'] . "_" . time();
        }

        /*if(+$id === 20) {
            $data['email'] = 'comm@mymbatrading.com';
        }*/

        // validation
        $user = new \Hyperwallet\Model\User();
        $user
            ->setClientUserId($data['client_user_id'])
            ->setProfileType(\Hyperwallet\Model\User::PROFILE_TYPE_INDIVIDUAL)
            ->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setEmail($data['email'])
            ->setAddressLine1($data['address_line_1'])
            ->setAddressLine2($data['address_line_2'])
            ->setCity($data['city'])
            ->setStateProvince($data['state_province'])
            ->setCountry($data['country'])
            ->setPhoneNumber($data['phone_number'])
            ->setPostalCode($data['postal_code'])
            ->setDateOfBirth(array_key_exists('date_of_birth', $data) && !!$data['date_of_birth'] ? new \DateTime($data['date_of_birth']) : null);

        DB::transaction(function() use ($user, $id){

            $hyperwalletUser = HyperwalletUser::where(['user_id' => $id])->first();

            if($hyperwalletUser != null)
            {
                throw new HyperwalletException("You already have an account");
            }

            try
            {
                $user = $this->hyperwallet->createUser($user);
            }
            catch(\Hyperwallet\Exception\HyperwalletApiException $ex)
            {
                HyperwalletException::throwException($ex);
            }
            catch(\Hyperwallet\Exception\HyperwalletException $ex)
            {
                HyperwalletException::throwException($ex);
            }

            /*try
            {
                $card = new \Hyperwallet\Model\PrepaidCard();
                $card
                    ->setCardPackage("DEFAULT")
                    ->setType("PREPAID_CARD");

                $card = $this->hyperwallet->createPrepaidCard($user->getToken(), $card);
            }
            catch(\Hyperwallet\Exception\HyperwalletException $ex)
            {
                // DO NOTHING
            }*/

            $hyperwalletUser = new HyperwalletUser();
            $hyperwalletUser->user_id = $id;
            $hyperwalletUser->token = $user->getToken();
            $hyperwalletUser->status = $user->getStatus();
            $hyperwalletUser->client_user_id = $user->getClientUserId();
            $hyperwalletUser->profile_type = $user->getProfileType();
            $hyperwalletUser->first_name = $user->getFirstName();
            $hyperwalletUser->last_name = $user->getLastName();
            $hyperwalletUser->date_of_birth = $user->getDateOfBirth() === null ? '' : $user->getDateOfBirth()->format('Y-m-d');
            $hyperwalletUser->email = $user->getEmail();
            $hyperwalletUser->phone_number = $user->getPhoneNumber();
            $hyperwalletUser->address_line_1 = $user->getAddressLine1();
            $hyperwalletUser->address_line_2 = $user->getAddressLine2() === null ? '' : $user->getAddressLine2();
            $hyperwalletUser->city = $user->getCity();
            $hyperwalletUser->state_province = $user->getStateProvince();
            $hyperwalletUser->country = $user->getCountry();
            $hyperwalletUser->postal_code = $user->getPostalCode();
            $hyperwalletUser->program_token = $user->getProgramToken();
            $hyperwalletUser->prepaid_card_token = isset($card) ? $card->getToken() : null;
            $hyperwalletUser->save();

        });

        return $this->getUser($id);
    }

    public function getUser($id)
    {
        $user = DB::table('users AS u')
            ->leftJoin('cm_hyperwallet_users AS hu', 'hu.user_id', '=', 'u.id')
            ->selectRaw("
                u.id AS user_id,
                IFNULL(hu.first_name, u.fname) first_name,
                IFNULL(hu.last_name, u.lname) last_name,
                IFNULL(hu.client_user_id, u.id) client_user_id,
                hu.token,
                hu.`status`,
                hu.date_of_birth,
                IFNULL(hu.email, u.email) email,
                hu.address_line_1,
                hu.address_line_2,
                hu.phone_number,
                hu.city,
                IFNULL(hu.state_province, '') state_province,
	            IFNULL(hu.country, '') country,
                hu.postal_code
            ")
            ->where('u.id', $id)
            ->first();

        if($user != null)
        {
            $user->invitation_link = $user->token ? config('services.hyperwallet.member_portal_url') : null;
        }

        return $user;
    }
}