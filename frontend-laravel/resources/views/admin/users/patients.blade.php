@extends('layouts.dashboard')

@section('title', 'Patient Management')

@section('dashboard_content')
<x-user-table :users="$users" role="Patient" roleId="3" :doctors="$doctors" />
@endsection
