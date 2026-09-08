<p><a href="{{ url('/public') }}">&larr; {{ __('app.public_back_to_catalog') }}</a></p>

@include('flashmsg.php')

<h1 class="title">{{ $plant->get('name') }}</h1>

@if ($plant->get('scientific_name'))
	<h2 class="subtitle"><em>{{ $plant->get('scientific_name') }}</em></h2>
@endif

<img class="public-plant-photo" src="{{ abs_photo($plant->get('photo')) }}" alt="{{ $plant->get('name') }}"/>

@if ((is_string($plant->get('tags'))) && (strlen(trim($plant->get('tags'))) > 0))
	<div>
		@foreach (preg_split('/\s+/', trim($plant->get('tags'))) as $public_plant_tag)
			@if (strlen($public_plant_tag) > 0)
				<span class="public-plant-tag">{{ $public_plant_tag }}</span>
			@endif
		@endforeach
	</div>
@endif

@if ((is_countable($photos)) && (count($photos) > 0))
	<div class="public-plant-gallery">
		@foreach ($photos as $public_plant_photo)
			<a href="{{ abs_photo($public_plant_photo->get('original')) }}" target="_blank">
				<img src="{{ abs_photo($public_plant_photo->get('thumb')) }}" alt="photo"/>
			</a>
		@endforeach
	</div>
@endif

<h3 class="title is-5">{{ __('app.public_journal_title') }}</h3>

@if ((is_countable($log_entries)) && (count($log_entries) > 0))
	@foreach ($log_entries as $public_log_entry)
		<div class="public-journal-entry" id="plant-log-entry-{{ $public_log_entry->get('id') }}">
			<div class="public-journal-entry-title">{{ $public_log_entry->get('title') }}</div>
			<div class="public-journal-entry-date">{{ $public_log_entry->get('entry_date') ? date('Y-m-d', strtotime($public_log_entry->get('entry_date'))) : date('Y-m-d', strtotime($public_log_entry->get('created_at'))) }}</div>

			@if (isset($log_entry_photos[$public_log_entry->get('id')]) && count($log_entry_photos[$public_log_entry->get('id')]) > 0)
				<div class="public-journal-entry-photos">
					@foreach ($log_entry_photos[$public_log_entry->get('id')] as $public_entry_photo)
						<a href="{{ abs_photo($public_entry_photo['original']) }}" target="_blank">
							<img src="{{ abs_photo($public_entry_photo['thumb']) }}" alt="photo"/>
						</a>
					@endforeach
				</div>
			@endif

			@if (strlen(trim($public_log_entry->get('content') ?? '')) > 0)
				<div class="public-journal-entry-content">{{ $public_log_entry->get('content') }}</div>
			@endif

			<div class="public-comments">
				<div class="public-comments-title">{{ __('app.public_comments_title') }}</div>

				@if (isset($log_entry_comments[$public_log_entry->get('id')]) && count($log_entry_comments[$public_log_entry->get('id')]) > 0)
					@foreach ($log_entry_comments[$public_log_entry->get('id')] as $public_comment)
						<div class="public-comment">
							<span class="public-comment-author">{{ $public_comment->get('author_name') ?: __('app.public_comment_anonymous') }}</span><span class="public-comment-date">{{ (new Carbon($public_comment->get('created_at')))->diffForHumans() }}</span>
							<div class="public-comment-text">{{ $public_comment->get('comment') }}</div>
						</div>
					@endforeach
				@else
					<p>{{ __('app.public_no_comments_yet') }}</p>
				@endif

				<form class="public-comment-form" method="POST" action="{{ url('/public/plant/' . $plant->get('id') . '/comment') }}">
					@csrf

					<input type="hidden" name="entry" value="{{ $public_log_entry->get('id') }}"/>

					<div class="public-hp-field" aria-hidden="true">
						<label>Website</label>
						<input type="text" name="website" tabindex="-1" autocomplete="off"/>
					</div>

					<div class="field">
						<label class="label">{{ __('app.public_comment_name_label') }}</label>
						<div class="control">
							<input type="text" class="input" name="name" maxlength="100">
						</div>
					</div>

					<div class="field">
						<label class="label">{{ __('app.public_comment_text_label') }}</label>
						<div class="control">
							<textarea class="textarea" name="comment" maxlength="2000" required></textarea>
						</div>
					</div>

					<div class="control">
						<input type="submit" class="button is-success" value="{{ __('app.public_comment_submit') }}"/>
					</div>
				</form>
			</div>
		</div>
	@endforeach
@else
	<p>{{ __('app.public_no_journal_entries') }}</p>
@endif
