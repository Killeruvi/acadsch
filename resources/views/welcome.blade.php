@extends('layouts.app')

@section('content')
    <x-nav />
    @include('welcome.hero')
    @include('welcome.vision')
    @include('welcome.philosophy')
    @include('welcome.ecosystem')
    @include('welcome.admissions')
    @include('welcome.cta')
@endsection