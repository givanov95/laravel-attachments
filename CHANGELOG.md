# Changelog

All notable changes to `givanov95/laravel-attachments` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-10-03

### Security
- Fixed missing authorization on the image/file routes (GHSA-6vf2-w3m8-6jqm). Up to 1.2.0 any authenticated user could soft-delete, reorder or download other users' attachments by id. `images.destroy`, `files.destroy`, `images.order`, `files.order` and `files.download` now authorize the attachment against its parent model (`fileable` / `imageable`) through the Gate: `view` for downloads, `update` for everything else (for every parent referenced in an `orderArray`). The check fails closed — a parent without a policy, or an orphaned attachment, answers `403`.
- `FileStr::generateUniqueFileName()` no longer keeps the client-supplied extension. The extension is derived from the detected MIME type, executable extensions (`php`, `phtml`, `phar`, `sh`, …) become `.bin`, and the stem is reduced to letters, digits, `_` and `-` (Unicode letters are kept, max 80 chars).

### Added
- `attachments.abilities` config to remap the two policy abilities (`view`, `update`).

### Upgrade notes
- **Breaking behaviour:** parent models that use `HasImages` / `HasFiles` need a policy with `view` and `update` abilities, otherwise the routes return `403`. Add the policies before updating. See the README's *Authorization* section.

## [1.2.0] - 2026-08-04

### Added
- `File` and `Image` now use `SoftDeletes` (new `deleted_at` migration, auto-run via `loadMigrationsFrom`; idempotent so existing installs only need `php artisan migrate`).
- Soft-delete-aware cascade in `HasFiles`/`HasImages`: deleting a parent that uses `SoftDeletes` now **soft-deletes** its attachments (keeping rows and physical files), and **restoring** the parent restores exactly the attachments removed with it. A parent without `SoftDeletes`, or a `forceDelete()`, permanently removes the rows and their physical files.
- `attachments:prune {--days=}` command (config `attachments.prune_days`, default 30) to permanently delete old soft-deleted attachments and their physical files.

### Changed
- Physical file deletion is now centralized in the models' `forceDeleted` hook — the disk is touched only on a permanent delete, never on a soft delete.
- `FileController::destroy` / `ImageController::destroy` now soft-delete the attachment (recoverable) and keep the physical file; it is removed on `forceDelete`/prune.

### Fixed
- Soft-deleting a parent model no longer permanently destroys its attachments. Previously the `deleting` hook hard-deleted the `files`/`images` rows even on a soft delete, so restoring the parent came back without its files (and physical files were left orphaned on disk).

## [0.1.1] - 2026-05-25

### Fixed
- `HasImages::$profileImageColumn` was a property with default `null`. Consumers who tried to override it on their model with `protected ?string $profileImageColumn = 'image_path';` hit a fatal "trait composition incompatible" error because PHP doesn't allow trait + class to redeclare a property with a different default. Replaced the property with a `profileImageColumn(): ?string` method that consumers override instead. **API change:** override the method, not the property.

## [0.1.0] - 2026-05-25

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
