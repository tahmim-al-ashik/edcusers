<?php

namespace App\Classes;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class OneSignalException extends \Exception
{
    //
}

class OneSignalClass {

    /**
     * @const OneSignal API URI
     */
    const API_URL = "https://onesignal.com/api/v1";


    /**
     * Transform the ResponseInterface in a more user-friendly array.
     * It returns the status code ("status" key) and the decoded json content ("data" key).
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    protected function formatResponse(ResponseInterface $response): array {
        return [
            'status' => $response->getStatusCode(),
            'data' => json_decode($response->getBody()->getContents(), true),
        ];
    }



    /**
     * Add a Notification to an application
     *
     * @param array $data Array of notification data to submit a new Notification object for
     * @return array OneSignal API response
     * @throws OneSignalException
     */
    public function postNotification(array $data = []) : array {
        $data['app_id'] = env('ONESIGNAL_APP_ID');

        $newClient = new Client();
        $headers = [];
        $headers['headers']['Authorization'] = 'Basic ' . env('ONESIGNAL_REST_API_KEY');
        $headers['headers']['Content-Type'] = 'application/json';
        $headers['json'] = $data;

        return self::formatResponse($newClient->post(self::API_URL . "/notifications", $headers));
    }

}
