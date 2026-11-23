@php
    $error = \App\Support\ErrorPage::context(request(), 404);
@endphp

@extends('errors::layout', ['error' => $error])

@section('page-title', $error['pageTitle'])
@section('skip-label', $error['skipLabel'])
@section('eyebrow', $error['eyebrow'])
@section('title', $error['title'])
@section('description', $error['description'])
@section('sr-status', $error['srStatus'])
@section('actions-label', $error['actionsLabel'])
@section('suggestions-label', $error['suggestionsLabel'])
@section('status', (string) $error['status'])

@section('artwork')
    @include('errors::partials.artwork', ['code' => 404, 'locale' => $error['locale']])
@endsection

@section('actions')
    @include('errors::partials.actions', ['actions' => $error['actions']])
@endsection

@section('suggestions')
    @include('errors::partials.suggestions', ['suggestions' => $error['suggestions']])
@endsection
