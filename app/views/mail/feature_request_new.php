<h1>{{ $item->get('title') }}</h1>

@if (strlen(trim($item->get('description'))) > 0)
<p>
    <pre>{{ $item->get('description') }}</pre>
</p>
@endif

<div>
    <div><strong>{{ __('app.feature_request_requested_by') }}:&nbsp;</strong>{{ $requester->get('name') }}</div>
    <div><a href="{{ workspace_url('/feature-requests') }}">{{ workspace_url('/feature-requests') }}</div>
</div>
