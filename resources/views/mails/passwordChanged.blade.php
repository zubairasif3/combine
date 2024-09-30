<h1>Hello {{$data["name"]}}</h1>
<p>Your password has been changed<p>
<p>Your new password is</p>
@if(isset($data["password"]))
<h3>{{$data["password"]}}</h3>
@endif
<p>You may login using this url {{url('/')}}</p>