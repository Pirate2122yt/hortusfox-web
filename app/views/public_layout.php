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
				background-color: rgb(24, 24, 24);
			}

			.public-catalog-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
				gap: 20px;
				margin-top: 20px;
			}

			.public-catalog-item {
				display: block;
				border-radius: 8px;
				overflow: hidden;
				background-color: rgb(35, 35, 35);
				color: rgb(220, 220, 220);
				text-decoration: none;
				transition: transform 0.15s ease;
			}

			.public-catalog-item:hover {
				transform: translateY(-3px);
				color: rgb(255, 255, 255);
			}

			.public-catalog-item-photo {
				width: 100%;
				aspect-ratio: 1 / 1;
				background-size: cover;
				background-position: center;
				background-color: rgb(50, 50, 50);
			}

			.public-catalog-item-title {
				padding: 12px;
				font-weight: bold;
			}

			.public-plant-photo {
				width: 100%;
				max-width: 420px;
				border-radius: 8px;
				margin-bottom: 20px;
			}

			.public-plant-gallery {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin-bottom: 20px;
			}

			.public-plant-gallery img {
				width: 100px;
				height: 100px;
				object-fit: cover;
				border-radius: 6px;
			}

			.public-plant-tag {
				display: inline-block;
				padding: 3px 10px;
				margin-right: 6px;
				margin-bottom: 6px;
				background-color: rgba(123, 123, 123, 0.3);
				color: rgb(190, 190, 190);
				border-radius: 10px;
				font-size: 0.85em;
			}

			.public-journal-entry {
				background-color: rgb(35, 35, 35);
				border-radius: 8px;
				padding: 16px;
				margin-bottom: 16px;
			}

			.public-journal-entry-title {
				font-weight: bold;
				font-size: 1.05em;
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
				color: rgb(190, 190, 190);
			}

			.public-comment-date {
				color: rgb(170, 170, 170);
				font-size: 0.85em;
				margin-left: 6px;
			}

			.public-comment-text {
				white-space: pre-wrap;
				word-wrap: break-word;
			}

			.public-comment-form {
				margin-top: 10px;
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
		</style>
	</head>

	<body>
		<nav class="navbar is-dark" role="navigation" aria-label="main navigation">
			<div class="navbar-brand">
				<a class="navbar-item navbar-item-brand is-font-title" href="{{ url('/public') }}">
					<img src="{{ asset('logo.png') }}"/>&nbsp;{{ app('workspace') }}
				</a>
			</div>
		</nav>

		<div class="container">
			<div class="columns">
				<div class="column is-1"></div>

				<div class="column is-10">
					<div class="content-inner">
						{%content%}
					</div>
				</div>

				<div class="column is-1"></div>
			</div>
		</div>
	</body>
</html>
