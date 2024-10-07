<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;
    public $table = "jobs";
    protected $fillable = ["customer_email","postcode","created_by", "added_by", "date", "engineer_id","agent_id", "job_invoice_no", "hand_overed_agent", "status"];
    
    public function engineer_user(){
        return $this->belongsTo(User::class,"engineer_id");
    }
    public function agent_assigned(){
        return $this->belongsTo(User::class,"agent_id");
    }
    public function handed_over(){
        return $this->belongsTo(User::class,"hand_overed_agent");
    }
    public function created_by_user(){
        return $this->belongsTo(User::class,"created_by");
    }
    public function added_by_user_name(){
        if ($this->created_by) {
            return $this->created_by_user->name;
        }else{
            return $this->added_by;
        }
    }
    public function contract(){
        return $this->hasOne(Contract::class,"job_id");
    }
    public function payment(){
        return $this->hasOne(Payment::class,"job_id");
    }
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($job) {
            $job->contract()->delete();
            $job->payment()->delete();
        });
    }
}
