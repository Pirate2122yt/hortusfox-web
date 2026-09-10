<h1>{{ $plant->get('name') }}</h1>

<p>
    <strong>{{ __('app.mail_public_comment_by') }}:&nbsp;</strong>{{ $authorName }}
</p>

@if ((isset($entry)) && ($entry) && (strlen(trim($entry->get('title'))) > 0))
<p>
    <strong>{{ __('app.mail_plant_log_entry') }}:&nbsp;</strong>{{ $entry->get('title') }}
</p>
@endif

<p>
    <pre>{{ $comment }}</pre>
</p>

<div>
    <a href="{{ workspace_url('/plants/details/' . $plant->get('id')) }}">{{ workspace_url('/plants/details/' . $plant->get('id')) }}</a>
</div>
