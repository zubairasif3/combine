<?php

namespace App\Http\Controllers;

use App\Events\OnNewUserCreation;
use App\Events\OnPasswordChange;
use App\Models\EngineerAvailability;
use App\Models\EngineerJobType;
use App\Models\EngineerModel;
use App\Models\JobType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Services\DistanceService;
use Illuminate\Support\Facades\DB;

class EngineerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $engineers = EngineerModel::all();
        return view("engineers/index",compact('engineers'));
    }

    public function JobTypeEngineers(Request $request){
        $jobTypes = JobType::all();
        return view("engineers.job_type_engineers",compact('jobTypes'));
    }

    public function AvailableToday(Request $request)
    {
        $availabilities = EngineerAvailability::where("date_start",date("Y-m-d"))->get();
        return view("engineers.availablt_today",compact('availabilities'));
    }

    public function WeeklyAvailableEngineers(Request $request){
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        // Retrieve EngineerAvailability records within the last 7 days
        $weeklyAvailable = EngineerAvailability::select(
            '*',
            DB::raw('TIMESTAMPDIFF(HOUR, start_time, end_time) as total_hours')
        )
        ->where('date_start', '>=', $startDate)
        ->orderBy('total_hours', 'desc')
        ->get();
        return view("engineers.weekly_available",compact('weeklyAvailable'));
    }

    public function SearchEngineer(Request $request)
    {
        $r_data = $request->all();
        if($request->has("post_codes"))
        {
            $search_code = $request->post_codes;
            $distanceService = new DistanceService();
            $post_codes = $distanceService->getRegionFromPostcode($search_code);

            $rating = $request->rating;
            $job_type_id = $request->job_type_id;
            $engineers = EngineerModel::whereHas('jobTypes', function ($query) use ($job_type_id) {
                $query->where('job_type_id', $job_type_id);
            })->whereHas("availability", function ($query) {
                $query->where("date_start", date("Y-m-d"));
            })->get();
            $filteredEngineers = $engineers->filter(function ($engineer) use ($post_codes) {
                $posts_array = explode(",", $engineer->postal_codes);
                return in_array($post_codes, $posts_array);
            });
            if ($rating) {
                $filteredEngineers = $filteredEngineers->where('rating', $rating);
            }
            $filteredEngineers = $filteredEngineers->sortBy('rating')->values();
            
            $engineers = new Collection($filteredEngineers->all());
            foreach ($engineers as $key => $engineer) {
                if ($engineer->lat !== null && $engineer->long !== null) {
                    $engineer['distance'] = $this->latlongDistance($search_code, $engineer->lat,$engineer->long);
                    if ($engineer['distance'] == null) {
                        $engineer['distance'] = $this->postcodeDistance($search_code, $engineer->home_postcode);
                    }
                }else{
                    $engineer['distance'] = $this->postcodeDistance($search_code, $engineer->home_postcode);
                }
            }
            $postcode = $post_codes;
        }else{
            $engineers = EngineerModel::orderBy("rating","desc")->get();
            $postcode = "";
        }

        $jobtypes = JobType::all();
        $jobtype = null;
        if($request->has("job_type_id"))
        {
            $jobtype = JobType::find($request->job_type_id);
        }
        return view("engineers.search",compact('engineers','jobtypes','r_data','jobtype','postcode'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("engineers/create");
    }

    public function showAvailabilityForm(Request $request,$id)
    {
        $engineer = EngineerModel::find($id);
        $jobtypes = JobType::all();
        return view("engineers.availability",compact('engineer','jobtypes'));
    }

    public function AddAvailability(Request $request)
    {
        $data = $request->all();
        if(isset($data["avail_method"]) && $data["avail_method"] == "update")
        {
            $availability =  EngineerAvailability::find($data["update_id"]);

        }else{
            $availability = new EngineerAvailability($data);
            if ($request->monthAvailability) {
                $availability->fill($data);
                $availability->save();
                for ($i=0; $i < 29; $i++) { 
                    $data['date_start'] = Carbon::parse($data['date_start'])->addDay();
                    $availability = new EngineerAvailability();
                    $availability->fill($data);
                    $availability->save();
                }
            }
        }

        $availability->fill($data);
        $availability->save();
        return back()->with("success","Engineer Availability Date Data Updated");
    }


    public function updateAvailabilityValue(Request $request,$id)
    {

        $engineer = EngineerModel::find($id);
        if($request->has("rating")){
            $engineer->rating = $request->rating;
        }
        if($request->has("home_postcode")){
            $engineer->home_postcode = $request->home_postcode;
        }
        $engineer->postal_codes = implode(",",$request->postcodes);
        $engineer->save();
        $jobTypes = $engineer->jobTypes;
        //deleting previously entered jobtypes
        foreach($jobTypes as $type)
        {
            $type->delete();
        }

        foreach($request->jobtypes as $type)
        {
            EngineerJobType::create([
                "engineer_id" => $engineer->id,
                "job_type_id" => $type
            ]);
        }

        return back()->with("success","Job and Location details updated");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $engineer = new EngineerModel;
        $engineer->fill($data);
        $engineer->save();
        $password = $data["password"];
        $data["password"] = Hash::make($data["password"]);
        unset($data["cpassword"]);
        $user = new User();
        $user->fill($data);
        $user->user_type_id = UserType::ENGINEER;
        $user->save();
        $engineer->user_id = $user->id;
        $engineer->save();
        $data["password"] = $password;
        event(new OnNewUserCreation($data));
        return redirect("engineers")->with("success","Engineer Saved Successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show(EngineerModel $engineerModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $engineer = EngineerModel::find($id);
        return view("engineers.edit",compact('engineer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $engineer = EngineerModel::find($id);
        $data = $request->all();
        $engineer->fill($data);
        $engineer->save();
        $user = $engineer->user;
        $isPasswordChanged = null;
        if(isset($data["password"]) && !empty($data["password"]))
        {
            $isPasswordChanged = $data["password"];//adding copy of password to receive in email
            $data["password"] = Hash::make($data["password"]);

        }else{
            unset($data["password"]);
        }
        $user->fill($data);
        $user->save();
        if($isPasswordChanged)
        {
            $data["password"] = $isPasswordChanged;
            $data["name"] = $engineer->name;
            event(new OnPasswordChange($data));
        }
        return redirect("engineers")->with("success","Engineer Updated Successfully");
    }

    public function RemoveEngineerAvailability(Request $request,$id)
    {
        $availability = EngineerAvailability::find($id);
        $availability->delete();
        return back()->with("error","Schedule Removed Successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $engineer = EngineerModel::find($id);
        foreach($engineer->jobTypes as $type){
            $type->delete();
        }

        foreach($engineer->availability as $available){
            $available->delete();
        }

        $user_id = $engineer->user_id;
        $engineer->delete();
        $user = User::find($user_id);
        $user->delete();
        return back()->with("error","Engineer Removed Successfully");
    }

    public function ResetPassword(Request $request,$email)
    {
        $hashEmail = $email;
        $user = User::whereRaw('MD5(email) = ?', [$hashEmail])->first();
        if ($user) {
            return view('reset_password');
        }else{
            return redirect("/")->with("error","Email is not correct");
        }
    }
    public function ResetPasswordPost(Request $request,$email)
    {
        $hashEmail = $email;
        $user = User::whereRaw('MD5(email) = ?', [$hashEmail])->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            return redirect("/");
        }else{
            return redirect("/")->with("error","Email is not correct");
        }
    }
}
