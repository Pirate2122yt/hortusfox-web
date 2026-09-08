<!doctype html>
<html lang="{{ getLocale() }}">
	<head>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow, noarchive"/>

		<link rel="icon" type="image/png" href="{{ asset('logo.png') }}"/>
		<link rel="stylesheet" type="text/css" href="{{ asset('css/bulma.css') }}"/>

		<title>{{ app('workspace') }} — {{ __('app.public_catalog') }}</title>

		<style>
			body {
				background-color: rgb(20, 22, 20);
				color: rgb(205, 205, 205);
			}

			a {
				color: rgb(120, 200, 140);
			}

			a:hover {
				color: rgb(150, 220, 165);
			}

			/*
			 * Bulma's .title/.subtitle/.label default to dark grays meant for
			 * light backgrounds (#363636/#4a4a4a) - practically invisible on
			 * this page's dark background - and .input/.textarea default to a
			 * plain white box, which clashes with the rest of the page. All
			 * three get an explicit, readable, dark-theme treatment here.
			 */
			.title {
				color: rgb(230, 235, 230);
			}

			.subtitle {
				color: rgb(190, 190, 190);
			}

			.label {
				color: rgb(210, 210, 210);
			}

			.help {
				color: rgb(170, 170, 170);
			}

			.input, .textarea {
				background-color: rgb(38, 40, 38);
				border-color: rgb(70, 70, 70);
				color: rgb(215, 215, 215);
				box-shadow: none;
			}

			.input::placeholder, .textarea::placeholder {
				color: rgb(130, 130, 130);
			}

			.input:focus, .textarea:focus {
				border-color: rgb(120, 200, 140);
				box-shadow: 0 0 0 0.125em rgba(120, 200, 140, 0.25);
			}

			/* Bulma's default is-success button (white text on #23d160) is
			   only ~2:1 contrast. This keeps the same green identity while
			   staying readable. */
			.button.is-success {
				background-color: rgb(22, 134, 61);
			}

			.button.is-success:hover, .button.is-success.is-hovered {
				background-color: rgb(18, 112, 51);
			}

			/* Layout */

			.public-navbar {
				position: sticky;
				top: 0;
				z-index: 10;
				border-bottom: 1px solid rgba(120, 200, 140, 0.25);
				box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.35);
			}

			.public-navbar .navbar-item-brand {
				font-weight: 600;
				letter-spacing: 0.02em;
			}

			.public-navbar .navbar-item-brand img {
				max-height: 2em;
			}

			.public-container {
				max-width: 980px;
				margin: 0 auto;
				padding: 32px 20px 60px 20px;
			}

			.public-catalog-header {
				margin-bottom: 10px;
			}

			.public-catalog-header .title {
				margin-bottom: 0.25em;
			}

			/* Bulma pulls a .subtitle up by -1.25rem to sit snugly under a
			   .title, sized for Bulma's own default 1.5rem title margin.
			   The tighter margin-bottom above needs a matching offset here,
			   or the subtitle overlaps the title text. */
			.public-catalog-header .subtitle {
				margin-top: 0.75em;
			}

			.public-catalog-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
				gap: 22px;
				margin-top: 25px;
			}

			.public-catalog-item {
				display: block;
				border-radius: 10px;
				overflow: hidden;
				background-color: rgb(32, 34, 32);
				border: 1px solid rgb(48, 50, 48);
				color: rgb(220, 220, 220);
				text-decoration: none;
				transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
			}

			.public-catalog-item:hover {
				transform: translateY(-4px);
				border-color: rgba(120, 200, 140, 0.5);
				box-shadow: 0 10px 24px 0 rgba(0, 0, 0, 0.4);
				color: rgb(255, 255, 255);
			}

			.public-catalog-item-photo {
				width: 100%;
				aspect-ratio: 1 / 1;
				background-size: cover;
				background-position: center;
				background-color: rgb(45, 45, 45);
				transition: transform 0.25s ease;
			}

			.public-catalog-item:hover .public-catalog-item-photo {
				transform: scale(1.04);
			}

			.public-catalog-item-body {
				padding: 12px 14px;
			}

			.public-catalog-item-title {
				font-weight: bold;
				word-wrap: break-word;
			}

			.public-catalog-item-subtitle {
				margin-top: 2px;
				font-size: 0.85em;
				color: rgb(175, 175, 175);
				word-wrap: break-word;
			}

			.public-catalog-empty {
				text-align: center;
				padding: 60px 20px;
				color: rgb(160, 160, 160);
			}

			.public-catalog-empty i {
				font-size: 2.2em;
				color: rgb(120, 200, 140);
				margin-bottom: 12px;
				display: block;
			}

			.public-plant-back {
				display: inline-block;
				margin-bottom: 18px;
				font-size: 0.95em;
			}

			.public-plant-photo {
				width: 100%;
				max-width: 460px;
				border-radius: 10px;
				margin-bottom: 18px;
				box-shadow: 0 8px 24px 0 rgba(0, 0, 0, 0.4);
			}

			.public-plant-tags {
				margin-bottom: 18px;
			}

			.public-plant-tag {
				display: inline-block;
				padding: 3px 10px;
				margin-right: 6px;
				margin-bottom: 6px;
				background-color: rgba(123, 123, 123, 0.3);
				color: rgb(200, 200, 200);
				border-radius: 10px;
				font-size: 0.85em;
			}

			.public-plant-info {
				background-color: rgb(30, 32, 30);
				border: 1px solid rgb(48, 50, 48);
				border-radius: 10px;
				padding: 18px 20px;
				margin-bottom: 24px;
			}

			.public-plant-info-title {
				font-weight: 600;
				font-size: 0.8em;
				letter-spacing: 0.06em;
				text-transform: uppercase;
				color: rgb(120, 200, 140);
				margin-bottom: 14px;
			}

			.public-plant-info-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
				gap: 16px;
			}

			.public-plant-info-item {
				display: flex;
				align-items: flex-start;
				gap: 10px;
			}

			.public-plant-info-item i {
				color: rgb(120, 200, 140);
				width: 1.1em;
				text-align: center;
				margin-top: 3px;
			}

			.public-plant-info-label {
				font-size: 0.78em;
				text-transform: uppercase;
				letter-spacing: 0.04em;
				color: rgb(160, 160, 160);
			}

			.public-plant-info-value {
				color: rgb(220, 220, 220);
				font-weight: 500;
			}

			.public-plant-gallery {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin-bottom: 26px;
			}

			.public-plant-gallery img {
				width: 100px;
				height: 100px;
				object-fit: cover;
				border-radius: 6px;
				transition: transform 0.15s ease;
			}

			.public-plant-gallery img:hover {
				transform: scale(1.06);
			}

			.public-journal-title {
				margin-bottom: 16px;
				padding-bottom: 8px;
				border-bottom: 1px solid rgba(120, 200, 140, 0.25);
			}

			.public-journal-entry {
				background-color: rgb(30, 32, 30);
				border: 1px solid rgb(48, 50, 48);
				border-left: 3px solid rgb(120, 200, 140);
				border-radius: 8px;
				padding: 16px 18px;
				margin-bottom: 18px;
			}

			.public-journal-entry-title {
				font-weight: bold;
				font-size: 1.05em;
				color: rgb(225, 225, 225);
			}

			.public-journal-entry-date {
				color: rgb(190, 190, 190);
				font-size: 0.85em;
				margin-bottom: 8px;
			}

			.public-journal-entry-content {
				white-space: pre-wrap;
				word-wrap: break-word;
				margin-bottom: 10px;
			}

			.public-journal-entry-photos {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin-bottom: 10px;
			}

			.public-journal-entry-photos img {
				width: 90px;
				height: 90px;
				object-fit: cover;
				border-radius: 6px;
			}

			.public-no-entries {
				text-align: center;
				padding: 30px 20px;
				color: rgb(160, 160, 160);
			}

			.public-comments {
				margin-top: 14px;
				padding-top: 12px;
				border-top: 1px solid rgba(123, 123, 123, 0.25);
			}

			.public-comments-title {
				font-weight: bold;
				font-size: 0.9em;
				color: rgb(190, 190, 190);
				margin-bottom: 8px;
			}

			.public-comment {
				padding: 6px 0;
				font-size: 0.9em;
			}

			.public-comment-author {
				color: rgb(210, 210, 210);
				font-weight: 600;
			}

			.public-comment-date {
				color: rgb(170, 170, 170);
				font-size: 0.85em;
				margin-left: 6px;
			}

			.public-comment-text {
				margin-top: 2px;
				color: rgb(205, 205, 205);
				white-space: pre-wrap;
				word-wrap: break-word;
			}

			.public-comment-form {
				margin-top: 12px;
			}

			.public-comment-form .field {
				margin-bottom: 10px;
			}

			.public-hp-field {
				position: absolute;
				left: -9999px;
				width: 1px;
				height: 1px;
				overflow: hidden;
			}

			.public-footer {
				text-align: center;
				margin-top: 40px;
				padding-top: 20px;
				border-top: 1px solid rgb(45, 45, 45);
				color: rgb(140, 140, 140);
				font-size: 0.85em;
			}

			.public-footer a {
				color: rgb(160, 160, 160);
			}

			/* Bulma only shows .navbar-menu at desktop widths, or on mobile
			   once a JS-driven burger toggles .is-active - this page has no
			   JS burger, so the menu is forced flex at every width instead. */
			.public-navbar {
				display: flex;
				align-items: center;
				justify-content: space-between;
				flex-wrap: wrap;
			}

			.public-navbar .navbar-menu {
				display: flex !important;
				background-color: transparent;
				box-shadow: none;
				padding: 0;
			}

			.public-navbar .navbar-end {
				display: flex;
				align-items: center;
			}

			.public-navbar .navbar-end a.navbar-item {
				color: rgb(190, 190, 190);
			}

			.public-navbar .navbar-end a.navbar-item:hover {
				color: rgb(255, 255, 255);
			}

			.public-identify-unavailable {
				text-align: center;
				padding: 60px 20px;
				color: rgb(160, 160, 160);
			}

			.public-identify-unavailable i {
				font-size: 2.2em;
				color: rgb(120, 200, 140);
				margin-bottom: 12px;
				display: block;
			}

			.public-identify-form-wrap {
				background-color: rgb(30, 32, 30);
				border: 1px solid rgb(48, 50, 48);
				border-radius: 10px;
				padding: 20px 22px;
				max-width: 480px;
			}

			.public-identify-remaining {
				margin-top: 12px;
				font-size: 0.85em;
				color: rgb(160, 160, 160);
			}

			.public-identify-results-title {
				margin-top: 30px;
			}

			.public-identify-results {
				display: flex;
				flex-direction: column;
				gap: 10px;
			}

			.public-identify-result {
				display: flex;
				align-items: center;
				gap: 16px;
				background-color: rgb(30, 32, 30);
				border: 1px solid rgb(48, 50, 48);
				border-radius: 8px;
				padding: 14px 16px;
			}

			.public-identify-result-score {
				flex: 0 0 auto;
				min-width: 3.2em;
				text-align: center;
				font-weight: 700;
				color: rgb(120, 200, 140);
			}

			.public-identify-result-name {
				color: rgb(225, 225, 225);
				font-size: 1.05em;
			}

			.public-identify-result-common {
				margin-top: 2px;
				font-size: 0.85em;
				color: rgb(170, 170, 170);
			}
		</style>
	</head>

	<body>
		<nav class="public-navbar navbar is-dark" role="navigation" aria-label="main navigation">
			<div class="navbar-brand">
				<a class="navbar-item navbar-item-brand is-font-title" href="{{ url('/public') }}">
					<img src="{{ asset('logo.png') }}"/>&nbsp;{{ app('workspace') }}
				</a>
			</div>

			@if ((app('public_plantid_enable')) && (app('plantrec_apikey')))
				<div class="navbar-menu">
					<div class="navbar-end">
						<a class="navbar-item" href="{{ url('/public/identify') }}"><i class="fas fa-camera-retro"></i>&nbsp;{{ __('app.public_identify_nav') }}</a>
					</div>
				</div>
			@endif
		</nav>

		<div class="public-container">
			{%content%}

			<div class="public-footer">
				<p>{{ __('app.public_footer_text') }} &middot; <a href="{{ url('/') }}">{{ __('app.login') }}</a></p>
			</div>
		</div>
	</body>
</html>
