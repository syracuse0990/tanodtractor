<?php

namespace App\Helpers;

use Exception;
use Twilio\Rest\Client;

class CommonHelper
{
    public static function sendSms($phone, $text = '')
    {
        try {

            //Twilio functionality
            // $text = '<p>Welcome to Tanod Tractor,</p><p>Use this code to verify your mobile number: ' . $userOtp->otp . '</p>';
            // $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
            // $message = $client->messages->create(
            //     $phone,
            //     [
            //         'from' => env('TWILIO_SMS_FROM'),
            //         'body' => $text,
            //     ]
            // );
            $body = "Welcome to Tanod Tractor,\n\n" . $text;
            $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
            $message = $client->messages->create(
                $phone,
                [
                    'from' => env('TWILIO_SMS_FROM'),
                    'body' => $body,
                ]
            );
            // $userOtp->update(['sent_at' => now()]);
            $data = [
                'is_sent' => true,
                'message_id' => $message->sid
            ];
            return $data;
        } catch (\Exception $e) {
            $data = [
                'is_sent' => false,
                'error' => $e->getMessage()
            ];
            return $data;
        }
    }

    public static function getDirection($request, $column)
    {
        if (!empty($request['sort_by'])) {
            if ($request['sort_by'] == $column) {
                if (!empty($request['sort_order'])) {
                    return  $request['sort_order'] == 'desc' ? 'asc' : 'desc';
                }
                return 'asc';
            }
            return '';
        }
        return '';
    }

    public static function getsortParams($request, $column)
    {
        $sort_order = 'asc';
        if (!empty($request['sort_by'])) {
            if ($request['sort_by'] == $column) {
                if (!empty($request['sort_order'])) {
                    $sort_order = $request['sort_order'] == 'desc' ? 'asc' : 'desc';
                }
            }
        }
        $request['sort_order'] = $sort_order;
        $request['sort_by'] = $column;
        return $request;
    }
}
