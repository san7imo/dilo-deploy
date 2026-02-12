# Backend Architecture (Dilo Records)

This document describes the backend architecture and the database schema at a high level.
It is based on the current Laravel codebase and migrations.

## 1) High-level architecture

### Tech stack
- Laravel 12 (PHP 8.2)
- Jetstream + Fortify (auth)
- Spatie Laravel Permission (roles/permissions)
- Inertia.js (backend renders Vue pages)
- ImageKit (media storage for images)

### Key folders
- app/Http/Controllers/Web
  - Public: public site
  - Admin: admin panel
  - Artist: artist portal
- app/Services
  - Business logic and aggregate queries (events, finance, releases, tracks, artists)
- app/Models
  - Eloquent models and relationships
- app/Http/Requests
  - Validation and authorization for forms
- routes/web.php
  - All web routes (public + private)

### Data flow (typical)
Route -> Controller -> Service -> Model (DB) -> Inertia response -> Vue page

### Auth & roles
- Users are authenticated via Jetstream/Fortify.
- Roles/permissions are managed with Spatie tables (roles, permissions, model_has_roles, etc).
- Roles used in app: admin, contentmanager, roadmanager, artist.

## 2) Database schema (main tables)

### users
- id, name, email, password, email_verified_at
- used by auth + roles/permissions

### artists
- id, user_id (nullable), name, slug, bio, country
- genre_id (nullable), presentation_video_url
- image fields: banner_home_url/banner_home_id, banner_artist_url/banner_artist_id,
  carousel_home_url/carousel_home_id, carousel_discography_url/carousel_discography_id
- social_links (json)

### genres
- id, name, slug

### releases
- id, artist_id, title, slug, cover_url, cover_id
- release_date, type (single, ep, album, mixtape, live, compilation)
- platform urls: spotify_url, youtube_url, apple_music_url, deezer_url, amazon_music_url,
  soundcloud_url, tidal_url
- description, extra (json)

### tracks
- id, release_id, title, track_number, duration
- cover_url, cover_id, preview_url
- platform urls: spotify_url, youtube_url, apple_music_url, deezer_url, amazon_music_url,
  soundcloud_url, tidal_url

### events
- id, main_artist_id, title, slug, description
- location, country, city, venue_address, event_date
- poster_url, poster_id
- event_type, status
- show_fee_total, currency, advance_percentage, advance_expected, full_payment_due_date
- is_paid, whatsapp_event, page_tickets

### event_payments
- id, event_id, created_by (user)
- payment_date, amount_original, currency
- exchange_rate_to_base, amount_base
- payment_method, is_advance, notes

### event_expenses
- id, event_id, created_by (user)
- expense_date, description, name, category
- amount_original, currency
- exchange_rate_to_base, amount_base
- receipt_url, receipt_id

### event_personal_expenses
- id, event_id, artist_id
- expense_date, expense_type, name, description, payment_method, recipient
- amount_original, currency
- exchange_rate_to_base, amount_base

## 3) Pivot tables (many-to-many)

- artist_event
  - artists <-> events (guest artists)
- track_artist
  - tracks <-> artists (collaborations)
- genre_release
  - genres <-> releases
- event_road_manager
  - events <-> users (road managers), includes payment_confirmed_at

## 4) Main relationships

- User 1..1 Artist (artists.user_id)
- Artist 1..N Releases
- Release 1..N Tracks
- Artist N..N Tracks (track_artist)
- Artist N..N Events (artist_event)
- Event 1..N EventPayments
- Event 1..N EventExpenses
- Event 1..N EventPersonalExpenses
- Event N..N RoadManagers (event_road_manager)
- Genre N..N Releases (genre_release)
- Artist N..1 Genre (artists.genre_id)

## 5) Domain rules (current behavior)

- Releases of type "single" auto-create a Track with the same title and platform URLs.
- Non-single releases are treated as collections; tracks are added separately.

## 6) Non-domain tables (framework)

- password_reset_tokens
- sessions
- personal_access_tokens (Sanctum)
- cache, jobs (queue)
- roles/permissions (Spatie)

## 7) Reference code locations

- Controllers: app/Http/Controllers/Web
- Services: app/Services
- Models: app/Models
- Requests (validation): app/Http/Requests
- Migrations: database/migrations
