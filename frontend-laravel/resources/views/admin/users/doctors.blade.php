@extends('layouts.dashboard')

@section('title', 'Doctor Management')

@section('dashboard_content')
<x-user-table :users="$users" role="Doctor" roleId="2" />
@endsection
