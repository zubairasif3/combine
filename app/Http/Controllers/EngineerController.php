<?php

namespace App\Http\Controllers;

use App\Events\OnNewUserCreation;
use App\Events\OnPasswordChange;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DB;
class EngineerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $engineers = User::where('user_type_id', 3)->get();
        return view("engineers/index",compact('engineers'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("engineers/create");
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
        ]);
        $data = $request->all();
        $password = $data["password"];
        $data['show_password'] = $data["password"];
        $data["password"] = Hash::make($data["password"]);
        $user = new User();
        $user->fill($data);
        $user->user_type_id = UserType::ENGINEER;
        $user->save();
        $data["password"] = $password;
        // event(new OnNewUserCreation($data));
        return redirect("engineers")->with("success","Engineer Saved Successfully");
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
        $engineer = User::find($id);
        return view("engineers.edit",compact('engineer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users,email,' . $id,
        ]);
        $engineer = User::find($id);
        $data = $request->all();
        $engineer->fill($data);
        $isPasswordChanged = null;
        if(isset($data["password1"]) && !empty($data["password1"]))
        {
            $isPasswordChanged = $data["password1"];//adding copy of password to receive in email
            $data["password"] = Hash::make($data["password1"]);

        }else{
            unset($data["password1"]);
        }
        // dd($data);
        // $addData = $data;
        $engineer->fill($data);
        $engineer->save();
        if($isPasswordChanged)
        {
            $data["password"] = $isPasswordChanged;
            $data["name"] = $engineer->name;
            // event(new OnPasswordChange($data));
        }else{
            $data['password'] = $engineer['show_password'];
        }
        // $html = view("mails.editUser",compact('data'))->render();
        // $this->InfoBipMail($data["email"],$html,"Welcome Pm247");
        return redirect("engineers")->with("success","Engineer Updated Successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $engineer = User::find($id);
        if ($engineer) {
            foreach ($engineer->engineer_jobs as $job) {
                $job->delete();
            }
            $engineer->delete();
            return back()->with('success', 'Engineer Removed Successfully');
        } else {
            return back()->with('error', 'Engineer not found');
        }
    }
}
