<h1>{{ $plant->get('name') }}</h1>

<p>
    <img src="{{ abs_photo($plant->get('photo')) }}" alt="plant-photo"/>
</p>

<div>
    <div><strong>{{ __('app.care_due_action') }}:&nbsp;</strong><span class="is-critical-info">{{ __('app.care_action_' . $action) }}</span></div>
    <div><a href="{{ workspace_url('/plants/details/' . $plant->get('id')) }}">{{ workspace_url('/plants/details/' . $plant->get('id')) }}</a></div>
</div>
