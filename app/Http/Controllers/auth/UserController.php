<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App\Models\Job;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
class UserController extends Controller
{

    public function index()
    {

        if(auth()->user())
        {
            if(auth()->user()->user_type_id !== UserType::ENGINEER){
                $currentDate = Carbon::now()->toDateString();
                $job1 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
                $job2 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', '>' , $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
                $job3 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('status', 'Completed')->orderBy('date', 'asc')->get();
                return view("jobs/index",compact('job1', 'job2', 'job3'));
            }
        }else{
            return view('welcome');
        }
    }


    public function ShowTvView(Request $request){
        $currentDate = Carbon::now()->toDateString();
        $jobs = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', $currentDate)->where('status','active')->get();
        return view("tv.tv-dashboard",compact('jobs'));
    }

    public function logout(Request $request){
        Auth::logout();
        return redirect("/");
    }

    public function processAuthRequest(Request $request)
    {
        
        $data = $request->all();
        unset($data["_token"]);
        if(Auth::attempt($data))
        {
            return redirect("/");
        }else{
            return back()->with("error","Wrong email or password");
        }
    }




    
    public function redirectToGoogle()
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $client->setAccessType('offline');
        $client->setPrompt('consent'); 
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        if ($request->has('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            
            // restrict gmail must have agentspm247@gmail.com
            $client->setAccessToken($token);  
           
            // Check if refresh token is present and save it
            if (isset($token['refresh_token'])) {
                $refreshToken = $token['refresh_token'];
                // Save the refresh token in the database for the authenticated user
                $updatedUser = User::find(auth()->user()->id);
                $updatedUser->gmail_refresh_token = $refreshToken;
                $updatedUser->save();
            } else {
                // Use the existing refresh token if no new one is provided
                $refreshToken = User::find(auth()->user()->id)->gmail_refresh_token;
                if (!$refreshToken) {
                    return redirect('/')->with('gmail_error', 'Failed to get a refresh token.');
                }
            }
        
            $service = new Gmail($client);
            $userProfile = $service->users->getProfile('me');
            $userEmail = $userProfile->getEmailAddress();
            if ($userEmail !== 'agentspm247@gmail.com') {
                return redirect('/')->with('gmail_wrong', 'Unauthorized email address. You are not contacting with agentspm247@gmail.com.');
            }
            $request->session()->put('gmail_token', $token);
            $updatedUser = User::find(auth()->user()->id);
            $updatedUser->gmail_login = 1;
            $updatedUser->save();
            return redirect()->route('mails.check');
        }

        return redirect()->route('login.google');

    }

}
