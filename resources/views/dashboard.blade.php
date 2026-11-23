@extends('layouts.app')

@section('content')
    @include('dashboard.quick-actions')

    @if($user->isAdmin())
        @include('dashboard.admin-overview')
    @endif

    @if($user->isTeacher())
        @include('dashboard.teacher-overview')
    @endif

    @if($user->isStudent())
        @include('dashboard.student-overview')
    @endif

    @include('dashboard.charts')
@endsection