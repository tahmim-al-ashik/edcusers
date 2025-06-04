<?php

namespace App\Http\Controllers;

use App\Classes\OneSignalClass;
use App\Classes\OneSignalException;
use App\Classes\ResponseWrapper;
use App\Models\MessageAndNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageAndNotificationController extends Controller
{

    public function createNewMessage($uid, $sender_uid, $title, $description) : bool {
        $query = new MessageAndNotification();
        $query->uid = $uid;
        $query->title = $title;
        $query->description = $description;
        $query->sender_uid = $sender_uid;
        $query->is_read = 0;
        return $query->save();
    }

    public function getPanelMessageList(Request $request, $receiver_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();

        $query = MessageAndNotification::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'message_and_notifications.sender_uid');
        $query->orderBy('message_and_notifications.created_at', 'DESC');
        $query->where('message_and_notifications.uid', '=', $receiver_id);
        $returned_data['results'] = $query->get(['user_profiles.full_name as sender_name', 'message_and_notifications.id', 'message_and_notifications.created_at', 'message_and_notifications.title', 'message_and_notifications.description', 'message_and_notifications.is_read']);
        return ResponseWrapper::End($returned_data);

    }

    public function getMessageList(Request $request, $auth_id) : JsonResponse {

        $returned_data = ResponseWrapper::Start();
        $userId = (new \App\Classes\CustomHelpers)->getUidByAuthId($auth_id);
        $totalSkip = $request->get('skip') !== null ? $request->get('skip') : 0;

        $query = MessageAndNotification::query();
        $query->leftJoin('user_profiles', 'user_profiles.uid', '=', 'message_and_notifications.sender_uid');
        $query->orderBy('message_and_notifications.created_at', 'DESC');
        $query->where('message_and_notifications.uid', '=', $userId);
        $query->skip($totalSkip)->take(10);
        $returned_data['results'] = $query->get(['user_profiles.full_name as sender_name', 'message_and_notifications.id', 'message_and_notifications.created_at', 'message_and_notifications.title', 'message_and_notifications.is_read']);
        return ResponseWrapper::End($returned_data);

    }

    public function getMessageDetails(Request $request, $id) : JsonResponse {
        $returned_data = ResponseWrapper::Start();
        $message = MessageAndNotification::where('id', '=', $id)->first(['id','description']);
        $message->is_read = 1;
        $message->save();

        $returned_data['results'] = $message['description'];
        return ResponseWrapper::End($returned_data);
    }


    /**
     * @throws OneSignalException
     */
    public function OneSignalSendExternalId($external_id, $title, $description) : array {
        return (new \App\Classes\OneSignalClass)->postNotification([
            "include_external_user_ids"=> [$external_id],
            "headings" => ["en" => $title],
            "contents" => ["en" => $description],
        ]);
    }


    /**
     * @throws OneSignalException
     */
    public function sendNotification(Request $request) : JsonResponse{

        $returned_data = ResponseWrapper::Start();

        $external_id = $request->get('user_auth_id');
        $title = $request->get('title');
        $description = $request->get('description');
        $returned_data['results'] = self::OneSignalSendExternalId($external_id, $title, $description);

        return ResponseWrapper::End($returned_data);
    }
}
