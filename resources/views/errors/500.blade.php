@extends('errors::layout')

@php
    $error = \App\Support\ErrorPage::context(request(), 500);
@endphp

@section('page-title', $error['pageTitle'])
@section('skip-label', $error['skipLabel'])
@section('eyebrow', $error['eyebrow'])
@section('title', $error['title'])
@section('description', $error['description'])
@section('sr-status', $error['srStatus'])
@section('actions-label', $error['actionsLabel'])
@section('status', (string) $error['status'])

@section('artwork')
    @include('errors::partials.artwork', ['code' => 500, 'locale' => $error['locale']])
@endsection

@section('actions')
    @include('errors::partials.actions', ['actions' => $error['actions']])
@endsection
