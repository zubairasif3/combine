@extends("layouts.dashboard")
@section("content")

<div class="row my-4">
    <form action="{{url('engineers')}}/{{$engineer->id}}" method="post" class="card border-0 shadow p-3 pb-4 mb-4">
        <input type="hidden" name="_method" value="put">
        <div class="card-header mx-lg-4 p-0 py-3 py-lg-4 mb-4 mb-md-0">
            @csrf
           <h3 class="h5 mb-0">Engineer Personal details</h3>
        </div>
        <div class="card-body p-0 p-md-4 pb-md-0">
           <div class="row justify-content-center">
            <div class="col-12 col-lg-6">
                <div class="form-group mb-4"><label for="cartInputCompany1">Engineer Name *</label> <input type="text" name="name" placeholder="Name" class="form-control"  value="{{ old('name', $engineer->name) }}" required></div>
             </div>
              <div class="col-12 col-lg-6">
                  <div class="form-group mb-4">
                     <label for="cartInputEmail1">Email address *</label> <input type="email" name="email" class="form-control"  value="{{ old('email', $engineer->email) }}" placeholder="example@company.com"  required>
                     {!! $errors->first('email', '<span class="text-danger">:message</span>') !!}
                  </div>
              </div>
              <div class="col-12 col-lg-6">
                 <div class="form-group mb-4"><label for="cartInputEmail1">Engineer Phone No *</label> <input name="phone" type="number" class="form-control"  value="{{ old('phone', $engineer->phone) }}" placeholder="07426593505" required></div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="form-group mb-4"><label for="cartInputEmail1">Engineer Password *</label> <input name="password1" type="password" class="form-control" placeholder="Leave empty if you do not want to change password"></div>
             </div>

             <div class="col-12"><button class="btn btn-gray-800 mt-3 animate-up-2" type="submit">Edit Engineer</button></div>
           </div>
        </div>
     </form>


@endsection
