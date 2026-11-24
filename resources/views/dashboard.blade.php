@extends('layouts.app')

@section('title', 'Dashboard — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="space-y-4">
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
</div>

@include('dashboard.charts')
@endsection
