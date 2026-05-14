@extends('layouts.main')

@section('title', 'Companies')
@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="row row-cols-1 row-cols-lg-3">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0">Total</p>
                                <h4 class="font-weight-bold">{{ $companyCount }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-cosmic text-white"><i class='bx bx-refresh'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0">Users</p>
                                <h4 class="font-weight-bold">16,352 <small class="text-success font-13">(+22%)</small></h4>
                                <p class="text-secondary mb-0 font-13">Analytics for last week</p>
                            </div>
                            <div class="widgets-icons bg-gradient-burning text-white"><i class='bx bx-group'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
            <!-- <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0">Time on Site</p>
                                <h4 class="font-weight-bold">34m 14s <small class="text-success font-13">(+55%)</small></h4>
                                <p class="text-secondary mb-0 font-13">Analytics for last week</p>
                            </div>
                            <div class="widgets-icons bg-gradient-lush text-white"><i class='bx bx-time'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
        <!--end row-->
        
        <h6 class="mb-0 text-uppercase">COMPANIES</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="example" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                             <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Logo</th>
                                <th>BG Color</th>
                                <th>Text Color</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                          @foreach($companies as $company)
                            <tr>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->email }}</td>
                                <td>{{ $company->phone }}</td>
                                <td>{{ $company->address }}</td>
                                <td>
                                    @if($company->logo)
                                        <img src="{{ asset('storage/logos/' . $company->logo) }}" width="50" height="50" alt="Logo">
                                    @endif
                                </td>
                                <td><span style="background-color: {{ $company->bg_color }}; padding: 4px 8px; display: inline-block;"></span></td>
                                <td><span style="color: {{ $company->txt_color }};">{{ $company->txt_color }}</span></td>
                                <td>{{ $company->created_at }}</td>
                                <td>{{ $company->updated_at }}</td>
                                <td>
                                    <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="{{ route('companies.destroy', $company->id) }}" method="POST" style="display:inline-block;" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" data-name="{{ $company->name }}">Delete</button>
                                    </form>
                                </td>
                            </tr>
                          @endforeach
                        </tbody>
                        <tfoot>
                             <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Logo</th>
                                <th>BG Color</th>
                                <th>Text Color</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>       
    </div>
</div>
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © 2021. All right reserved.</p>
</footer>
@endsection