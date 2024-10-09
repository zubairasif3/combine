<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Gmail;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentDate = Carbon::now()->toDateString();
        $job = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', '<' , $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
        $job1 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
        $job2 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', '>' , $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
        $job3 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('status', 'Completed')->orderBy('date', 'asc')->get();
        return view("jobs/index",compact('job', 'job1', 'job2', 'job3'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("jobs/create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $job = new Job();
        $data['created_by'] = auth()->user()->id;
        $data['date'] = Carbon::today();
        $job->fill($data);
        $job->save();
        return redirect("jobs")->with("success","Job Saved Successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $engineers = User::where('user_type_id', 3)->get();
        $agents = User::where('user_type_id', 2)->get();
        $job = Job::find($id);
        return view("jobs/edit",compact('engineers','job','agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $job = Job::find($id);
        $prev_engineer_id = (int) $job->engineer_id;
        $prev_agent_id = (int) $job->agent_id;
        $job->fill($data);
        $job->save();

        if ($prev_engineer_id !== (int) $job->engineer_id || $prev_agent_id !== (int) $job->agent_id) {
            if ($job->engineer_user) {
                $message = "Dear ".($job->engineer_user ? $job->engineer_user->name : '').", we assign you the agent name ".($job->agent_assigned ? $job->agent_assigned->name : '')." on this job which postcode is ". $job->postcode .".";
                $correctphone = ($job->engineer_user ? $job->engineer_user->phone : '');
                if (substr($correctphone, 0, 1) === '0') {
                    $correctphone = substr($correctphone, 1);
                }
                $correctphone = 44 . $correctphone;
                $dataa = $this->messageBirdSMS($correctphone,$message);
                $this->InfoBipMail($job->engineer_user->email,$message,"Agent Assign");
            }
        }
        return redirect("jobs")->with("success","Job Updated Successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $job = Job::find($id);
        if ($job) {
            $job->delete();
            return back()->with("error","Job Removed Successfully");
        }else {
            return back()->with('error', 'Job not found');
        }
    }

    // assign functions

    // GET: Assign Engineer
    public function AssignEngineer($id)
    {
        $engineers = User::where('user_type_id', 3)->get();
        $job = Job::find($id);

        return view('jobs.assign_engineer', compact('engineers', 'job'));
    }

    // POST: Assign Engineer
    public function AssignEngineerPost(Request $request, $id)
    {
        $job = Job::find($id);
        $job->update([
            'engineer_id' => $request->input('engineer_id')
        ]);

        return back()->with('success', 'Engineer assigned successfully.');
    }

    public function AssignAgent($id)
    {
        $agents = User::where('user_type_id', 2)->get();
        $job = Job::find($id);

        return view('jobs.assign_agent', compact('agents', 'job'));
    }

    public function AssignAgentPost(Request $request, $id)
    {
        $job = Job::find($id);
        $job->update([
            'agent_id' => $request->input('agent_id')
        ]);

        if ($job->engineer_user) {
            $message = "Dear ".($job->engineer_user ? $job->engineer_user->name : '').", we assign you the agent name ".($job->agent_assigned ? $job->agent_assigned->name : '')." on this job which postcode is ". $job->postcode .".";
            $correctphone = ($job->engineer_user ? $job->engineer_user->phone : '');
            if (substr($correctphone, 0, 1) === '0') {
                $correctphone = substr($correctphone, 1);
            }
            $correctphone = 44 . $correctphone;
            $dataa = $this->messageBirdSMS($correctphone,$message);
            $this->InfoBipMail($job->engineer_user->email,$message,"Agent Assign");
        }
        return redirect()->route('jobs.index')->with('success', 'Agent assigned successfully.');
    }

    public function AssignHandover($id)
    {
        $job = Job::find($id);
        $agents = User::where('user_type_id', 2)->get();

        return view('jobs.assign_hand_over', compact('agents', 'job'));
    }

    public function AssignHandoverPost(Request $request, $id)
    {
        $job = Job::find($id);
        $job->update([
            'hand_overed_agent' => $request->input('hand_overed_agent')
        ]);

        // $message = "Dear ".$job->engineer_user->name.", we assign you the new agent name ".$job->handed_over->name." on this job which postcode is ". $job->postcode .".";
        // $correctphone = $job->engineer_user->phone;
        // if (substr($correctphone, 0, 1) === '0') {
        //     $correctphone = substr($correctphone, 1);
        // }
        // $correctphone = 44 . $correctphone;
        // $dataa = $this->messageBirdSMS($correctphone,$message);
        // $this->InfoBipMail($job->engineer_user->email,$message,"Agent Assign");
        return redirect()->route('jobs.index')->with('success', 'Agent handover assigned successfully.');
    }


    // Check latest Data Ajax function
    public function latestData(Request $request)
    {
        $loadedTime = Carbon::parse($request->input('loaded_time'));
        $sevenDaysAgo = Carbon::now()->subDays(3);
        $job = Job::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();

        if ($job) {
            $currentDate = Carbon::now()->toDateString();
            $job = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', '<' , $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
            $job1 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
            $job2 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', '>' , $currentDate)->where('status', 'Active')->orderBy('date', 'asc')->get();
            $job3 = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('status', 'Completed')->orderBy('date', 'asc')->get();
            $html = view('includes.mainDashboard', ['jobs' => $job])->render();
            $html1 = view('includes.mainDashboard', ['jobs' => $job1])->render();
            $html2 = view('includes.mainDashboard', ['jobs' => $job2])->render();
            $html3 = view('includes.mainDashboard', ['jobs' => $job3])->render();
            return response()->json([
                'status' => 'success',
                'data0' => $html,
                'data1' => $html1,
                'data2' => $html2,
                'data3' => $html3
            ]);
        } else {
            return response()->json(['status' => 'no_updates']);
        }
    }
    // Check latest Data Ajax function
    public function latestTvData(Request $request)
    {
        $loadedTime = Carbon::parse($request->input('loaded_time'));

        // Query to get new or updated jobs since the page was loaded
        $job = Job::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();

        if ($job) {
            $currentDate = Carbon::now()->toDateString();
            $jobs = Job::where('created_at', '>=', Carbon::now()->subDays(3))->where('date', $currentDate)->where('status','active')->get();
            $html = view('tv.data.assign', ['jobs' => $jobs])->render();
            return response()->json([
                'status' => 'success',
                'data' => $html
            ]);
        } else {
            return response()->json(['status' => 'no_updates']);
        }
    }


    // Contracts Functions
    public function Contracts()
    {
        $jobs = Job::where('created_at', '>=', Carbon::now()->subDays(7))->latest()->get();
        return view("jobs/contracts",compact('jobs'));
    }
    public function ContractSent($id)
    {
        $contract = new Contract;
        $contract->job_id = $id;
        $contract->sent_by = auth()->user()->id;
        $contract->status = "sent";
        $contract->sent_time = Carbon::now();
        $contract->save();
        return redirect("contracts")->with("success","Contract Sent Successfully");
    }
    public function ContractReceived($id)
    {
        $contract = Contract::find($id);
        $contract->status = "received";
        $contract->received_time = Carbon::now();
        if ($contract->job->engineer_user) {
            $html = view("mails.contractSign",compact('contract'))->render();
            $this->InfoBipMail($contract->job->engineer_user->email,$html,"Contract has been signed");
            $message = "Dear ".$contract->job->engineer_user->name.", The contract has been signed for the Job at ".$contract->job->postcode.". Please only proceed when payment has also been confirmed as paid. You will be informed once payment is received by email and sms.";
            $correctphone = $contract->job->engineer_user->phone;
            if (substr($correctphone, 0, 1) === '0') {
                $correctphone = substr($correctphone, 1);
            }
            $correctphone = 44 . $correctphone;
            $dataa = $this->messageBirdSMS($correctphone,$message);
        }
        $contract->inform_time = Carbon::now();
        $contract->save();
        return redirect("contracts")->with("success","Contract Received Successfully");
    }

    // Payment Functions

    public function Payments()
    {

        $jobs = Job::where('created_at', '>=', Carbon::now()->subDays(7))->latest()->get();

        return view("jobs/payments",compact('jobs'));
    }
    public function PaymentSent($id)
    {
        $payment = new Payment;
        $payment->job_id = $id;
        $payment->sent_by = auth()->user()->id;
        $payment->status = "sent";
        $payment->sent_time = Carbon::now();
        $payment->save();
        return redirect("payments")->with("success","Payment Sent Successfully");
    }
    public function PaymentReceived($id)
    {
        $payment = Payment::find($id);
        $payment->status = "received";
        $payment->received_time = Carbon::now();
        if ($payment->job->engineer_user) {
            $html = view("mails.paymentSign",compact('payment'))->render();
            $this->InfoBipMail($payment->job->engineer_user->email,$html,"Payment has been Received");
            $message = "Dear ".$payment->job->engineer_user->name.", The Payment has been received for the Job at ".$payment->job->postcode.". Please only proceed when contract has also been confirmed as paid. You will be informed once the contract is received by email and sms";
            $correctphone = $payment->job->engineer_user->phone;
            if (substr($correctphone, 0, 1) === '0') {
                $correctphone = substr($correctphone, 1);
            }
            $correctphone = 44 . $correctphone;
            $dataa = $this->messageBirdSMS($payment->job->engineer_user->phone,$message);
        }
        $payment->inform_time = Carbon::now();
        $payment->save();
        return redirect("payments")->with("success","Payment Received Successfully");
    }

    // Check latest Data Ajax function
    public function contractLatestData(Request $request)
    {
        $loadedTime = Carbon::parse($request->input('loaded_time'));
        $sevenDaysAgo = Carbon::now()->subDays(7);
        // Query to get new or updated jobs since the page was loaded
        $contract = Contract::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();
        $payment = Payment::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();

        if ($contract || $payment) {
            $contracts = Contract::latest()->take(10)->get()->reject(function ($contract) use ($sevenDaysAgo) {
                            return $contract->job->created_at < $sevenDaysAgo;
                        });
            $payments = Payment::latest()->take(10)->get()->reject(function ($payment) use ($sevenDaysAgo) {
                            return $payment->job->created_at < $sevenDaysAgo;
                        });
            $html = view('includes.contractMainDashboard', ['contracts' => $contracts , 'payments' => $payments])->render();
            return response()->json([
                'status' => 'success',
                'data' => $html
            ]);
        } else {
            return response()->json(['status' => 'no_updates']);
        }
    }
    public function contractLatestTvData(Request $request)
    {
        $loadedTime = Carbon::parse($request->input('loaded_time'));

        // Query to get new or updated jobs since the page was loaded
        $job = Job::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();
        $contract = Contract::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();
        $payment = Payment::where('created_at', '>', $loadedTime)->orWhere('updated_at', '>', $loadedTime)->first();

        if ($job || $contract || $payment) {
            $jobs = Job::where('created_at', '>=', Carbon::now()->subDays(7))->latest()->get();
            $html = view('tv.data.contract', ['jobs' => $jobs])->render();
            return response()->json([
                'status' => 'success',
                'data' => $html
            ]);
        } else {
            return response()->json(['status' => 'no_updates']);
        }
    }

    // Email-check function

    public function refreshTokenIfNeeded()
    {
        $user = User::find(auth()->user()->id);
        $refreshToken = $user->gmail_refresh_token;

        if (!$refreshToken) {
            return "error";
        }

        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($newToken['access_token'])) {

            if (isset($newToken['refresh_token'])) {
                // Save the new refresh token if it's returned
                $user->gmail_refresh_token = $newToken['refresh_token'];
            }

            $user->save();
            return $newToken['access_token'];
        }

        return "error";
    }

    public function listEmails1(Request $request)
    {
        $client = new GoogleClient();
        if (!$request->session()->has('gmail_token')) {
            $accessToken = $this->refreshTokenIfNeeded();
            if($accessToken == "error"){
                return redirect()->route('login.google');
            }
            $request->session()->put('gmail_token', $accessToken);
            $client->setAccessToken($accessToken);
        }else{
            try {
                $token = $request->session()->get('gmail_token');
                $client->setAccessToken($token);
                if ($client->isAccessTokenExpired()) {
                    $accessToken = $this->refreshTokenIfNeeded();
                    if($accessToken == "error"){
                        return redirect()->route('login.google');
                    }
                    $request->session()->put('gmail_token', $accessToken);
                    $client->setAccessToken($accessToken);
                }

            } catch (\Throwable $th) {
                return redirect()->route('login.google');
            }
        }
        $data = $this->MailChecker($client);
        dd($data);
    }

    public function GmailErrorCheck(Request $request){
        $updatedUser = User::where('is_login',1)->where('gmail_login',1)->first();
        if (!$updatedUser) {
            return response()->json(['status' => 'no gmail login']);
        }
        return response()->json(['status' => 'gmial login']);
    }

    public function listEmails(Request $request)
    {
        $client = new GoogleClient();
        if (!$request->session()->has('gmail_token')) {
            $accessToken = $this->refreshTokenIfNeeded();
            if($accessToken == "error"){
                return redirect()->route('login.google');
            }
            $request->session()->put('gmail_token', $accessToken);
            $client->setAccessToken($accessToken);
        }else{
            try {
                $token = $request->session()->get('gmail_token');
                $client->setAccessToken($token);
                if ($client->isAccessTokenExpired()) {
                    $accessToken = $this->refreshTokenIfNeeded();
                    if($accessToken == "error"){
                        return redirect()->route('login.google');
                    }
                    $request->session()->put('gmail_token', $accessToken);
                    $client->setAccessToken($accessToken);
                }
            } catch (\Throwable $th) {
                return redirect()->route('login.google');
            }
        }
        $this->MailChecker($client);
        return redirect('/');
    }

    public function GmailCheck(Request $request)
    {
        $client = new GoogleClient();
        if (!$request->session()->has('gmail_token')) {
            $accessToken = $this->refreshTokenIfNeeded();
            if($accessToken == "error"){
                $updatedUser = User::find(auth()->user()->id);
                $updatedUser->gmail_login = 0;
                $updatedUser->save();
                return response()->json(['status' => 'error']);
            }
            $request->session()->put('gmail_token', $accessToken);
            $client->setAccessToken($accessToken);
        }else{
            try {
                $token = $request->session()->get('gmail_token');
                $client->setAccessToken($token);
                if ($client->isAccessTokenExpired()) {
                    $accessToken = $this->refreshTokenIfNeeded();
                    if($accessToken == "error"){
                        $updatedUser = User::find(auth()->user()->id);
                        $updatedUser->gmail_login = 0;
                        $updatedUser->save();
                        return response()->json(['status' => 'error']);
                    }
                    $request->session()->put('gmail_token', $accessToken);
                    $client->setAccessToken($accessToken);
                }

            } catch (\Throwable $th) {
                $updatedUser = User::find(auth()->user()->id);
                $updatedUser->gmail_login = 0;
                $updatedUser->save();
                return response()->json(['status' => 'error']);
            }
        }
        $changes = $this->MailChecker($client);
        $updatedUser = User::find(auth()->user()->id);
        $updatedUser->gmail_login = 1;
        $updatedUser->save();
        if ($changes > 0) {
            return response()->json(['status' => 'data_updated']);
        }
        return response()->json(['status' => 'no_error']);
    }

    public function MailChecker($client){
        $service = new Gmail($client);
        $user = 'me';
        $results = $service->users_messages->listUsersMessages($user, ['maxResults' => 25]);
        $messages = [];

        foreach ($results->getMessages() as $message) {
            $msg = $service->users_messages->get($user, $message->getId());
            $payload = $msg->getPayload();
            $headers = collect($payload->getHeaders());

            // Retrieve the subject header safely
            $subjectHeader = $headers->first(fn($header) => $header->getName() === 'Subject');
            $subject = $subjectHeader ? $subjectHeader->getValue() : null;

            // Retrieve the to header safely
            $toHeader = $headers->first(fn($header) => $header->getName() === 'To');
            $toEmail = $toHeader ? $toHeader->getValue() : null;

            // Extract the message body
            $body = $this->getBodyFromPayload($payload);
            $body = trim($body);

            $emailData = [
                'id' => $msg->getId(),
                'subject' => $subject,
                'toEmail' => $toEmail,
                'body' => $body,
            ];
            $messages[] = $emailData;
        }

        $changes = 0;
        foreach ($messages as $message) {
            $changes += $this->assignMailGetter($message, $changes);
            $changes += $this->contractMailGetter($message, $changes);
        }
        return $changes;
    }

    private function assignMailGetter($message, $changes){
        $subject = $message['subject'];
        $body = preg_replace('/\s+/', ' ', strip_tags($message['body']));
        $body = trim($body);
        if (strpos($subject, "PM247 || NEW JOB BOOKED") !== false || strpos($subject, "PM247 || JOB CANCELLED") !== false) {
            $details = $this->extractJobDetails($body);

            $job = Job::withTrashed()->where('customer_email', $details['email'])
                      ->where('postcode', $details['postcode'])
                    //   ->where('date', $details['date'])
                      ->where('added_by', $details['username'])
                      ->first();
            if (strpos($subject, "PM247 || NEW JOB BOOKED") !== false) {
                if (!$job) {
                    Job::create([
                        'customer_email' => $details['email'],
                        'postcode' => $details['postcode'],
                        'date' => $details['date'],
                        'added_by' => $details['username'],
                    ]);
                    $changes++;
                }
            } elseif (strpos($subject, "PM247 || JOB CANCELLED") !== false) {
                if ($job && $job->deleted_at == null) {
                    $job->delete();
                    $changes++;
                }
            }
        }
        return $changes;
    }
    private function contractMailGetter($message, $changes){
        $subject = $message['subject'];
        $body = preg_replace('/\s+/', ' ', strip_tags($message['body']));
        $body = trim($body);
        if (strpos($subject, "Contract") !== false && strpos($subject, "sent to") !== false) {
            if (preg_match('/Recipient\s*(.*?)\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $body, $matches)) {
                $email = $matches[2];
                $job = Job::where('customer_email',$email)->latest()->first();
                if($job && $job->contract == null){
                    $this->ContractSent($job->id);
                    $changes++;
                }
            }
        } else if (strpos($subject, "Contract") !== false && strpos($subject, "has been signed by") !== false) {
            if (preg_match('/Recipient\s*(.*?)\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $body, $matches)) {
                $email = $matches[2];
                $job = Job::where('customer_email',$email)->latest()->first();
                if($job && $job->contract){
                    $contract = $job->contract;
                    if($contract->status !== 'received'){
                        $this->ContractReceived($contract->id);
                        $changes++;
                    }
                }
            }
        } else if (strpos($subject, "A new invoice was created for ") !== false) {
            if (preg_match('/Customer\s*(.*?)\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $body, $matches)) {
                $email = $matches[2];
                $job = Job::where('customer_email',$email)->latest()->first();
                if($job && $job->payment == null){
                    $this->PaymentSent($job->id);
                    $changes++;
                }
            }
        } else if (strpos($subject, "An invoice was paid by") !== false) {
            if (preg_match('/Customer\s*(.*?)\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $body, $matches)) {
                $email = $matches[2];
                $job = Job::where('customer_email',$email)->latest()->first();
                if($job && $job->payment){
                    $payment = $job->payment;
                    if($payment->status !== 'received'){
                        $this->PaymentReceived($payment->id);
                        $changes++;
                    }
                }
            }
        } else if (strpos($subject, "Bank Transfer Received") !== false) {
            if (preg_match('/Payment from\s+([a-zA-Z\s]+)\s+([a-zA-Z]{2})\d{5}([a-zA-Z]+)(\d{2})([a-zA-Z0-9]+)(.{2})\s*$/m', $body, $matches)) {
                $invoice_number = $matches[3];
                dd($matches);
                $job = Job::where('job_invoice_no',$invoice_number)->latest()->first();
                if(!$job){
                    dd($matches);
                    $postcode = $matches[5];
                    // $job = Job:::whereRaw('REPLACE(postcode, " ", "") = ?', [str_replace(' ', '', $extractedPostcode)])->first();
                }
                if($job){
                    if($job->payment){
                        $payment = $job->payment;
                        if($payment->status !== 'received'){
                            $this->PaymentReceived($payment->id);
                            $changes++;
                        }
                    }else{
                        $payment = new Payment;
                        $payment->job_id = $job->id;
                        $payment->save();
                        $this->PaymentReceived($payment->id);
                    }
                }
            }
        } else if (strpos($subject, "VoltPayByLinkMail") !== false) {
            $email = $message['toEmail'];
            $job = Job::where('customer_email',$email)->latest()->first();
            if($job && $job->payment == null){
                $this->PaymentSent($job->id);
                $changes++;
            }
        }
        return $changes;
    }

    private function getBodyFromPayload($payload) {
        $body = '';
        $parts = $payload->getParts();

        if (empty($parts)) {
            // Single part email
            $body = $payload->getBody()->getData();
            // Decode base64url
            if ($body) {
                $body = strtr($body, '-_', '+/');
                $body = base64_decode($body);
            }
        } else {
            // Multi-part email
            foreach ($parts as $part) {
                $mimeType = $part->getMimeType();
                $bodyPart = $part->getBody()->getData();
                if ($bodyPart) {
                    $bodyPart = strtr($bodyPart, '-_', '+/');
                    $bodyPart = base64_decode($bodyPart);
                    // Append body part to body
                    if ($mimeType === 'text/plain') {
                        $body .= $bodyPart;
                    } elseif ($mimeType === 'text/html') {
                        // You might want to process HTML content separately if needed
                        $body .= strip_tags($bodyPart);
                    }
                }
            }
        }

        return $body;
    }

    private function extractJobDetails($body) {
        preg_match('/Owner email address:\s*([^\s]+)/', $body, $email);
        preg_match('/Job Created User Name:\s*(.+?)\s*Job Created Time:/s', $body, $username);
        preg_match('/Job Postcode:\s*(.+?)\s*Job Address:/s', $body, $postcode);
        preg_match('/Job Date:\s*([^\s]+)/', $body, $date);

        return [
            'email' => $email[1],
            'username' => trim(preg_replace('/[\s>]+/', ' ', $username[1])),
            'postcode' => trim(preg_replace('/[\s>]+/', ' ', $postcode[1])),
            'date' => date("Y-m-d", strtotime(str_replace('/', '-', $date[1]))),
        ];
    }

}
