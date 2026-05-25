# Changelog

All notable changes to `givanov95/laravel-attachments` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial extraction from `laravel-starter`.
- `Image` and `File` polymorphic Eloquent models with appended `url` accessor (uses `Storage::disk(config('attachments.disk'))->url()`).
- `create_images_table` and `create_files_table` migrations (morph + section + order + size + timestamps).
- `HasImages` trait: `setImages($collection, $section)` (accumulates across calls + sections), `getGroupedImages($sections)`, `firstImage` / `latestImage` ofMany helpers, `refreshProfileImagePath()` uses `saveQuietly()` to avoid infinite loops, `deleting()` hook cascades attachment delete.
- `HasFiles` trait: matching API for documents.
- `UploadHelper::uploadMultipleImages($request, $key, $directory, $disk)` — moves UploadedFile[] to the configured disk and returns ready-to-stage `Image` models. Same for `uploadMultipleFiles`.
- `Services\Support\FileStr::generateUniqueFileName()` — produces `<sanitized-stem>_<time>_<uniqid>.<ext>` filenames.
- `ImageController` + `FileController` with id-based routes: `images.destroy`, `images.order`, `files.destroy`, `files.download`, `files.order`. Order endpoints accept `orderArray: [id, …]` and rewrite the `order` column to 1-indexed array position.
- `AttachmentsServiceProvider` auto-loads the package migrations and routes; publishes config and migrations.

### Configuration
- `config/attachments.php` exposes `disk` (default `public`, env `ATTACHMENTS_DISK`) and `middleware` (default `['web', 'auth']`).

### Notes
- `Image::$fillable` includes `imageable_type` and `imageable_id`; same for `File` — so the models can be created with `Image::create([...])` directly, in addition to the trait's `setImages()` flow.
