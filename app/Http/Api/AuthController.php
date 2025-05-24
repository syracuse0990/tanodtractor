<?php

namespace App\Http\Api;

use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EmailQueue;
use App\Models\Jimi;
use App\Models\User;
use App\Models\UserOtp;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        $userData = $request->all();
        $user = User::where('email', $request->email)->first();
        if (!empty($user)) {
            $user->email_verification_otp = User::generateEmailVerificationOtp();
            $user->save();
        } else {
            $userData['email_verification_otp'] = User::generateEmailVerificationOtp();
            $userData['password']  = Hash::make('password');
            $user = User::create($userData);
        }
        EmailQueue::add([
            'to' => $user->email,
            'subject' => "Verification Code",
            'view' => 'mail',
            'type' => 0,
            'viewArgs' => [
                'name' => $user->full_name,
                'body' => "Welcome to Tanod Tractor, use this code to verify your email address: " . $user->email_verification_otp
            ]
        ]);
        return returnSuccessResponse('Otp send to your email.', $user->otpResponse());
    }

    /* @var $request object of request class
     * @var $user object of user class
     * @return object with user
     * This function use to user register
     */
    public function register(Request $request, User $user)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
            'confirm_password' => 'required',
            'device_type' => 'required|boolean',
            // 'fcm_token' => 'required',
            'otp' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        $userData = $request->all();
        $user = User::where('email', $request->email)->first();
        if (empty($user)) {
            return returnNotFoundResponse("User not found with this email.");
        }
        if (empty($user->email_verification_otp)) {
            return returnErrorResponse("User already registered.");
        }
        if ($user->email_verification_otp != $userData['otp']) {
            return returnErrorResponse("Otp not matched.");
        }
        $user->name = $request->name;
        $user->password = Hash::make($userData['password']);
        $user->state_id = User::STATE_ACTIVE;
        $user->role_id = User::ROLE_FARMER;
        // $user->fcm_token = $userData['fcm_token'];
        $user->email_verified_at = Carbon::now();
        $user->email_verification_otp = null;
        $user->save();
        if ($request->file('profile_photo_path')) {
            $path = $request->file('profile_photo_path')->store('image', 'public');
            $user->profile_photo_path = $path;
            $user->save();
        }
        return returnSuccessResponse('You are registered successfully.', $user->jsonResponse());
    }

    /**
     * @var $request object of request class
     * @var $user object of user class
     * @return object with user
     * This function use to user login
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required',
            'password' => 'required',
            'device_type' => 'required',
            'fcm_token' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        $inputArr = $request->all();

        $userObj = User::where('email', $inputArr['email'])->first();
        if (empty($userObj))
            return returnNotFoundResponse('User Not found.');

        if (!in_array($userObj->role_id, [User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SUB_ADMIN])) {
            if ($userObj->state_id == User::STATE_INACTIVE) {
                return returnErrorResponse("Your account is inactive please contact with admin.");
            }

            if (empty($userObj->email_verified_at)) {
                return notAuthorizedResponse('Please verify your email.', $userObj->jsonResponse());
            }

            // if (empty($userObj->phone_verified_at)) {
            //     $returnResponse = [
            //         'id' => $userObj->id,
            //         'phone_verified' => false
            //     ];
            //     return returnSuccessResponse('Please verify your phone number.', $returnResponse);
            // }
        }

        if (!Auth::attempt(['email' => $inputArr['email'], 'password' => $inputArr['password']])) {
            return notAuthorizedResponse('Invalid credentials');
        }

        $userObj->device_type = $inputArr['device_type'];
        $userObj->fcm_token = $inputArr['fcm_token'];
        $userObj->tokens()->delete();
        $authToken = $userObj->createToken('authToken')->plainTextToken;
        $userObj->remember_token = $authToken;
        $userObj->save();

        $returnArr = $userObj->jsonResponse();

        $message = 'Logged in successfully.';
        if ($userObj->role_id == User::ROLE_ADMIN) {
            $message = "Admin logged in successfully.";
        } elseif ($userObj->role_id == User::ROLE_FARMER) {
            $message = "User logged in successfully.";
        }

        return returnSuccessResponse($message, $returnArr);
    }

    /* @var $request object of request class
    * @var $user object of user class
    * @return object with user
    * This function use to user logout 
    */
    public function logout(Request $request)
    {
        $userObj = $request->user();
        if (!$userObj) {
            return response()->json(['error' => "You are not authorized"], 400);
        }

        $userObj->tokens()->delete();
        // $userObj->fcm_token = null;
        $userObj->save();
        return returnSuccessResponse('User logged out successfully');
    }

    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function details()
    {
        $user = Auth::user();
        if ($user) {
            return response()->json(['success' => $user], 200);
        } else {
            return response()->json(['error' => 'No data found'], 400);
        }
    }

    /** 
     * change password api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function updatePassword(Request $request)
    {
        # Validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ((Hash::check(request('old_password'), Auth::user()->password)) == false) {
            return response()->json(['error' => "Check your old password."], 400);
        } else if ((Hash::check(request('new_password'), Auth::user()->password)) == true) {
            return response()->json(['message' => "Please enter a password which is not similar then current password."], 400);
        } else {
            User::where('id', Auth::user()->id)->update(['password' => Hash::make(request('new_password'))]);
            Auth::guard('sanctum')->user()->tokens()->delete();
            return response()->json(['success' => "Password updated successfully."], 200);
        }
    }

    /** 
     * forgot password api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function forgotPassword(Request $request)
    {
        # Validation
        $request->validate([
            'email' => 'required|email'
        ]);
        $user = User::where('email', request('email'))->first();
        if (!$user) {
            return response()->json(['message' => "Email does'nt exists."], 400);
        } else {
            $status = Password::sendResetLink(
                $request->only('email')
            );
            if ($status == 'passwords.sent') {
                $user->email_verified_at = Carbon::now();
                return returnSuccessResponse("Reset password link sent on your email id.");
            } else {
                return notAuthorizedResponse('Bad Request', $status);
            }
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {

        $user = User::findorFail(Auth::user()->id);
        $user->update($request->all());
        if ($request->file('profile_photo_path')) {
            $path = $request->file('profile_photo_path')->store('image', 'public');
            $user->profile_photo_path = $path;
            $user->save();
        }
        $returnArr = $user->jsonResponse();
        return returnSuccessResponse('Profile updated successfully', $returnArr);
    }

    /** 
     * Verify mobile number.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function verifyMobile(Request $request)
    {
        $rules = [
            'user_id' => 'required',
            'phone' => [
                'required',
                'numeric',
                'integer',
                'digits:10',
                Rule::unique('users', 'phone')->ignore($request->user_id),
            ],
            'phone_country' => 'required',
            'country_code' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $user = User::findorFail($request->user_id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 400);
            }
            $userOtp = UserOtp::where(['phone_country' => $request->phone_country, 'phone' => $request->phone, 'state_id' => UserOtp::STATE_ACTIVE])->first();
            if (!$userOtp) {
                $userOtp = User::generateOtp($request->phone_country, $request->phone, $user);
            }
            $twilioResponse = CommonHelper::sendSms($userOtp, $request->phone_country . $request->phone);
            if ($twilioResponse['is_sent'] != true) {
                return notAuthorizedResponse('An error occurred:' . $twilioResponse['error'], [], 'bad request');
            }
            return returnSuccessResponse('We have sent a verification code to your mobile number.', [
                'phone_country' => $request->phone_country,
                'phone' => $request->phone,
                'otp' => $userOtp->otp
            ]);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /* @var $request object of request class
    * @var $user object of user class
    * @return object with user
    * This function use to send user otp for verify email
    */
    public function verifyOtp(Request $request, User $user)
    {
        $rules = [
            'user_id' => 'required',
            'otp' => 'required|digits:4',
            'phone' => [
                'required',
                'numeric',
                'integer',
                'digits:10',
                Rule::unique('users', 'phone')->ignore($request->user_id),
            ],
            'phone_country' => 'required',
            'country_code' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        $userOtp = UserOtp::where(['phone_country' => $request->phone_country, 'phone' => $request->phone, 'state_id' => UserOtp::STATE_ACTIVE, 'otp' => $request->otp])->orderBy('id', 'DESC')->first();
        if (!$userOtp) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $datetime1 = new DateTime($userOtp->sent_at);
        $datetime2 = new DateTime(date('Y-m-d H:i:s'));
        $interval = $datetime1->diff($datetime2);
        $secondsDifference = $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->days * 86400);
        if ($secondsDifference > 30) {
            UserOtp::where(['phone_country' => $request->phone_country, 'phone' => $request->phone, 'state_id' => UserOtp::STATE_ACTIVE, 'otp' => $request->otp])->delete();
            return response()->json(['error' => 'Invalid OTP'], 400);
        }
        $user = User::findorFail($request->user_id);
        $user->phone_verified_at = Carbon::now();
        $user->phone_country = $request->phone_country;
        $user->phone = $request->phone;
        $user->country_code = $request->country_code;
        $user->fcm_token = $request->fcm_token;
        $user->save();
        UserOtp::where(['phone_country' => $request->phone_country, 'phone' => $request->phone, 'state_id' => UserOtp::STATE_ACTIVE, 'otp' => $request->otp])->delete();

        $authToken = $user->createToken('authToken')->plainTextToken;
        $user->remember_token = $authToken;
        $user->save();
        $returnArr = $user->jsonResponse();
        return returnSuccessResponse('Otp verified successfully', $returnArr);
    }

    //Function for test jimi apis

    public function getData()
    {
        // // $data = (new Jimi())->sendCommand('869066060241453', 106, 'SPEED,ON,{0},{1},{2}#', ['SPEED']);
        // // $data = (new Jimi())->getSharingLocationUrl('869066060239416');
        // $data = (new Jimi())->getDeviceLocation(['869066060239739']);
        // // $data = (new Jimi())->getDeviceMilage(['869066060239739'], '2023-11-15 00:00:00', '2023-11-15 23:59:59');
        // return $data;

        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            (new Jimi())->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'method' => 'jimi.user.child.list',
            'sign_method' => 'md5',
            'target' => 'Admin_LAPC',
            'timestamp' => $gmt_date,
            'v' => '0.9',
        ];
        dd($data);
        // $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imeis' . $data['imeis']  . 'map_type' . $data['map_type'] . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        // $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();
            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                (new Jimi())->getToken(true);
            }
            return $e->getMessage();
        }
    }
}
