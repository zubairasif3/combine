<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Services\MessageBirdService;
use App\Models\InfoBipModel;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $messageBird;
    public function __construct(MessageBirdService $messageBird)
    {
        $this->messageBird = $messageBird;
    }
    public function messageBirdSMS($recipient, $message)
    {
        $response = $this->messageBird->sendSMS($recipient, $message);
        return response()->json(['message' => $response]);
    }

    // infobipMail
    public function InfoBipMail($email,$html,$subject)
    {
        return InfoBipModel::SendEmail($email,$html,$subject);
    }
    
}
