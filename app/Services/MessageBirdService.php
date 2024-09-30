<?php

namespace App\Services;

use GuzzleHttp\Client;
use MessageBird\Objects\Message;

class MessageBirdService
{

    public function sendSMS($recipient, $message)
    {
        $client = new Client([
            'verify' => false,
        ]);

        // MessageBird API key
        $apiKey = config('messagebird.api_key'); // Make sure this key is set in your .env file as MESSAGEBIRD_API_KEY

        // API endpoint for sending SMS via MessageBird
        $url = 'https://rest.messagebird.com/messages';

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'AccessKey ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'recipients' => $recipient,
                    'originator' => 'PM247', // Replace with your sender name
                    'body' => $message,
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            return response()->json(['message' => 'Message sent successfully!', 'response' => $responseBody], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
