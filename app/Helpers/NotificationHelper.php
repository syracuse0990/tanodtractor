<?php

namespace App\Helpers;

use App\Models\Notification;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\OAuth2;
use Google_Client;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{

    public static function sendPushNotification($fcmToken, $data)
    {
        try {
            $keyFilePath = env('FIREBASE_FILE');
            $client = new Google_Client();
            $client->setAuthConfig($keyFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $oauth2 = new OAuth2([
                'audience' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
                'issuer' => $client->getClientId(),
                'clientSecret' => $client->getClientSecret(),
                'signingAlgorithm' => 'RS256',
                'tokenUri' => 'https://oauth2.googleapis.com/token',
            ]);

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $keyFilePath
            );

            $accessToken = $credentials->fetchAuthToken()['access_token'];

            $fields = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => 'Tanod Tractor',
                        'body' => $data['body'],
                    ],
                    'data' => [
                        'title' => strval('Tanod Tractor'),
                        'body' => strval($data['body']),
                        'message' => strval($data['message']),
                        'user_id' => strval($data['user_id']),
                        'notification_type' => strval($data['notification_type']),
                        "booking_id" => isset($data['booking_id']) ? strval($data['booking_id']) : null,
                        "tractor_id" => isset($data['tractor_id']) ? strval($data['tractor_id']) : null,
                        "geofence_id" => isset($data['geofence_id']) ? strval($data['geofence_id']) : null,
                        'notification_id' => isset($data['notification_id']) ? strval($data['notification_id']) : null,
                        'imei' => isset($data['imei']) ? strval($data['imei']) : null,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => intval(1),
                            ],
                        ],
                    ],
                ],
            ];

            $headers = [
                "Authorization: Bearer " . $accessToken,
                'Content-Type: application/json'
            ];

            $firebaseUrl = 'https://fcm.googleapis.com/v1/projects/digigulay/messages:send';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $firebaseUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);
            Log::debug("Notification response: " . json_encode($response));
            return $ch;
        } catch (Exception $e) {
            Log::debug("call fcm error: " . $e->getMessage());
            echo 'Message: ' . $e->getMessage();
        }
    }

    public static function sendTicketNotification($fcmToken, $data)
    {
        try {
            $keyFilePath = env('FIREBASE_FILE');
            $client = new Google_Client();
            $client->setAuthConfig($keyFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $oauth2 = new OAuth2([
                'audience' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
                'issuer' => $client->getClientId(),
                'clientSecret' => $client->getClientSecret(),
                'signingAlgorithm' => 'RS256',
                'tokenUri' => 'https://oauth2.googleapis.com/token',
            ]);

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $keyFilePath
            );

            $accessToken = $credentials->fetchAuthToken()['access_token'];

            $fields = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => 'Tanod Tractor',
                        'body' => $data['body'],
                    ],
                    'data' => [
                        'title' => strval('Tanod Tractor'),
                        'body' => strval($data['body']),
                        'user_id' => strval($data['user_id']),
                        'notification_type' => strval($data['notification_type']),
                        'message' => strval($data['message']),
                        'notification_id' => strval($data['notification_id']),
                        'ticket_id' => isset($data['ticket_id']) ? strval($data['ticket_id']) : null,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => intval(1),
                            ],
                        ],
                    ],
                ],
            ];

            $headers = [
                "Authorization: Bearer " . $accessToken,
                'Content-Type: application/json'
            ];

            $firebaseUrl = 'https://fcm.googleapis.com/v1/projects/digigulay/messages:send';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $firebaseUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);
            Log::debug("Notification response: " . json_encode($response));
            return $ch;
        } catch (Exception $e) {
            Log::debug("call fcm error: " . $e->getMessage());
            echo 'Message: ' . $e->getMessage();
        }
    }
}
