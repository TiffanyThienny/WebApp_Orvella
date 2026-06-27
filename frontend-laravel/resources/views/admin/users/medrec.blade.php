@extends('layouts.dashboard')

@section('title', 'Medical Record Staff')

@section('dashboard_content')
<x-user-table :users="$users" role="Medical Record" roleId="4" />
@endsection
