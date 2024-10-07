<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserType;
use App\Models\EngineerModel;
use App\Models\EngineerJobType;
use App\Models\EngineerAvailability;
use App\Models\JobType;
use App\Models\InfoBipModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class EngineerController extends Controller
{
    public function AllJopTypes(Request $request)
    {
        $allJobs = JobType::all();
        return response()->json(["status" => "success", "allJobs" => $allJobs], 200);
    }
    public function engineerDetail(Request $request, $id)
    {
        $engineer = EngineerModel::where('id', $id)->with('jobTypes', 'availability')->first();
        if (!$engineer) {
            return response()->json(["status" => "error", "message" => "Engineer not found."], 404);
        }

        return response()->json(["status" => "success",  "engineer" => $engineer], 200);
    }

    public function signin(Request $request)
    {
        // Validate the request
        $rules=array(
            'email' => 'required|email',
            'password' => 'required'
        );
        $messages=array(
            'email.required' => 'Emai required.',
            'email.email' => 'Email is not in correct format.',
            'password.required' => 'Password Field is required.',
        );
        $validator=Validator::make($request->all(),$rules,$messages);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["error"=>$messages], 400);
        }

        $user = User::where('email', $request->email)->where('user_type_id', 3)->first();

        if (!$user) {
            return response()->json(["status" => "error", "message" => "Email does not exist."], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(["status" => "error", "message" => "Wrong Password."], 200);
        }

        $engineer = EngineerModel::where('user_id', $user->id)->with('jobTypes', 'availability')->first();

        return response()->json([
            "status" => "success",
            "data" => [
                "user" => $user,
                "engineer" => $engineer,
            ]
        ], 200);
    }

    public function updateEngineer(Request $request, $id)
    {
        // Validate the request
        $rules = [
            'postcodes' => 'required|array',
            'jobtypes' => 'required|array|exists:jobtypes,id',
        ];
        $messages = [
            'postcodes.required' => 'The postcodes field is required.',
            'postcodes.array' => 'The postcodes field must be an array.',
            'jobtypes.required' => 'The jobtypes field is required.',
            'jobtypes.array' => 'The jobtypes field must be an array.',
            'jobtypes.exists' => 'The selected jobtype ID is invalid.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $engineer = EngineerModel::find($id);
        if (!$engineer) {
            return response()->json(["status" => "error", "message" => "Engineer not found."], 404);
        }

        $engineer->postal_codes = implode(",", $request->postcodes);
        if (isset($request->home_postcode)) {
            $engineer->home_postcode = $request->home_postcode;
        }
        $engineer->save();

        // Delete previously entered job types
        $engineer->jobTypes()->delete();

        // Add new job types
        foreach ($request->jobtypes as $type) {
            EngineerJobType::create([
                "engineer_id" => $engineer->id,
                "job_type_id" => $type,
            ]);
        }

        return response()->json(["status" => "success", "message" => "Engineer Updated successfully"], 200);
    }

    public function addAvailability(Request $request)
    {
        // Validate the request
        $rules = [
            'engineer_id' => 'required|exists:engineers,id',
            'date_start' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
        ];
        $messages = [
            'engineer_id.required' => 'The engineer ID is required.',
            'engineer_id.exists' => 'The selected engineer ID does not exist.',
            'date_start.required' => 'The start date is required.',
            'date_start.date' => 'The start date is not a valid date.',
            'start_time.required' => 'The start time is required.',
            'start_time.date_format' => 'The start time must be in the format HH:MM.',
            'end_time.required' => 'The end time is required.',
            'end_time.date_format' => 'The end time must be in the format HH:MM.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->all();
        $availability = new EngineerAvailability;
        $availability->fill($data);
        $availability->save();
        if ($request->monthAvailability > 0) {
            $allAvailabilities = array(); 
            array_push($allAvailabilities, $availability);
            for ($i=0; $i < 29; $i++) { 
                $data['date_start'] = Carbon::parse($data['date_start'])->addDay();
                $availability = new EngineerAvailability();
                $availability->fill($data);
                $availability->save();
                array_push($allAvailabilities, $availability);
            }
        }else{
            $allAvailabilities = $availability; 
        }

        return response()->json(["status" => "success", "data" => $allAvailabilities, "message" => "Engineer Availability Added Successfully."], 200);
    }

    public function updateAvailability(Request $request, $id)
    {
        // Validate the request
        $rules = [
            'engineer_id' => 'required|exists:engineers,id',
            'date_start' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
        ];
        $messages = [
            'engineer_id.required' => 'The engineer ID is required.',
            'engineer_id.exists' => 'The selected engineer ID does not exist.',
            'date_start.required' => 'The start date is required.',
            'date_start.date' => 'The start date is not a valid date.',
            'start_time.required' => 'The start time is required.',
            'start_time.date_format' => 'The start time must be in the format HH:MM.',
            'end_time.required' => 'The end time is required.',
            'end_time.date_format' => 'The end time must be in the format HH:MM.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $availability = EngineerAvailability::find($id);
        if (!$availability) {
            return response()->json(["status" => "error", "message" => "Availability not found."], 404);
        }
        $availability->fill($request->all());
        $availability->save();
        return response()->json(["status" => "success", "message" => "Engineer Availability Updated Successfully."], 200);
    }
    public function DeleteAvailability(Request $request, $id)
    {
        $availability = EngineerAvailability::find($id);
        if (!$availability) {
            return response()->json(["status" => "error", "message" => "Availability not found."], 404);
        }
        $availability->delete();
        return response()->json(["status" => "success",  "message" => "Availability deleted successfully."], 200);
    }

    
    public function AddLatLong(Request $request)
    {
        // Validate the request
        $rules = [
            'engineer_id' => 'required|exists:engineers,id',
            'lat' => 'required',
            'long' => 'required',
        ];
        $messages = [
            'engineer_id.required' => 'The engineer ID is required.',
            'engineer_id.exists' => 'The selected engineer ID does not exist.',
            'lat.required' => 'the engineer Latitude is required.',
            'long.required' => 'the engineer Longitude is required.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->all();
        $engineer = EngineerModel::find($data['engineer_id']);
        $engineer->fill($data);
        $engineer->save();

        return response()->json(["status" => "success", "data" => $engineer, "message" => "Engineer Latitude and Longitude Updated Successfully."], 200);
    }


    public function DeleteEngineer(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(["status" => "error", "message" => "Engineer not found."], 404);
        }
        if ($user->engineerRow) {
            $engineer = $user->engineerRow;
            foreach($engineer->jobTypes as $type){
                $type->delete();
            }
    
            foreach($engineer->availability as $available){
                $available->delete();
            }
            $user->engineerRow->delete();
        }
        $user->delete();
        return response()->json(["status" => "success",  "message" => "Engineer deleted successfully."], 200);
    }
    public function signUp(Request $request)
    {
        // Validate the request
        $rules=array(
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'home_postcode' => 'required'
        );
        $messages=array(
            'name.required' => 'Name Field is required.',
            'email.required' => 'Emai Field is required.',
            'email.email' => 'Email is not in correct format.',
            'password.required' => 'Password Field is required.',
            'home_postcode.required' => 'Home Postcode Field is required.',
        );
        $validator=Validator::make($request->all(),$rules,$messages);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["error"=>$messages], 400);
        }

        $data = $request->all();
        $password = $data["password"];
        $data["password"] = Hash::make($data["password"]);
        $user = new User();
        $user->fill($data);
        $user->user_type_id = UserType::ENGINEER;
        $user->save();
        $engineer = new EngineerModel;
        $engineer->fill($data);
        $engineer->user_id = $user->id;
        $engineer->save();

        return response()->json([
            "status" => "success",
            "message" => "Engineer SignUp Succussfully.",
            "data" => [
                "user" => $user,
                "engineer" => $engineer,
            ]
        ], 200);
    }

    public function EditProfile(Request $request, $id)
    {
        // Validate the request
        $rules=array(
            'name' => 'required',
        );
        $messages=array(
            'name.required' => 'Name Field is required.',
        );
        $validator=Validator::make($request->all(),$rules,$messages);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["error"=>$messages], 400);
        }

        $data = $request->all();
        $user = User::find($id);
        $user->name = $data["name"];
        $user->save();
        $engineer = EngineerModel::where('user_id',$user->id)->first();
        $engineer->name = $data["name"];
        $engineer->save();

        return response()->json([
            "status" => "success",
            "message" => "Engineer Profile Updated Succussfully.",
            "data" => [
                "user" => $user,
                "engineer" => $engineer,
            ]
        ], 200);
    }
    public function ChangePassword(Request $request, $id)
    {
        // Validate the request
        $rules=array(
            'current_password' => 'required',
            'password' => 'required',
        );
        $messages=array(
            'current_password.required' => 'Current Password Field is required.',
            'password.required' => 'Password Field is required.',
        );
        $validator=Validator::make($request->all(),$rules,$messages);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["error"=>$messages], 400);
        }

        $user = User::find($id);
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(["status" => "error", "message" => "Current Password is wrong."], 200);
        }
        $data = $request->all();
        $password = $data["password"];
        $data["password"] = Hash::make($data["password"]);
        $user->password = $data["password"];
        $user->save();

        return response()->json([
            "status" => "success",
            "message" => "Engineer Password Change Succussfully.",
            "data" => [
                "user" => $user,
            ]
        ], 200);
    }
    public function ForgetPassword(Request $request)
    {
        ob_start();
        // Validate the request
        $rules=array(
            'email' => 'required',
        );
        $messages=array(
            'email.required' => 'Email Field is required.',
        );
        $validator=Validator::make($request->all(),$rules,$messages);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["error"=>$messages], 400);
        }
        $user = User::where('email',$request->email)->first();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "Engineer Not Found."], 400);
        }
        $hashEmail = md5($user->email);
        $reset_link = url('reset-password/' . $hashEmail);
        $html = view("mails.forgetPassword",compact('user','reset_link'))->render();
        InfoBipModel::SendEmail($user->email,$html,"Forget Password");
        ob_end_clean();
        return response()->json([
            "status" => "success",
            "message" => "Forget Password Mail Send Successfully.",
        ], 200);
    }
}
