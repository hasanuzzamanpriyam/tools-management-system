# Account Settings & Password — Design

Date: 2026-09-17
Status: Approved

## Goal

Replace the `/settings` placeholder with a working account settings page: update
name/email, upload/remove a profile picture, change password, and a
forgot/reset password flow driven by emailed links. Social login (GitHub/Google)
is a separate follow-up task.

## Current state

- `/settings` route renders `Placeholder` ("coming soon").
- No profile endpoints: `AuthController` exposes only register/login/logout/me.
- No `avatar` column; no password change or reset flow.
- `FILESYSTEM_DISK=local` (private storage — avatars served via an authenticated
  endpoint, no public symlink needed). Dev `MAIL_MAILER=log`.
- `password_reset_tokens` table exists (framework `Password` broker usable as-is).

## Approach

Hand-rolled API + SPA pages matching existing patterns (Sanctum tokens, `api`
client, Tailwind v4 / slate + indigo, TDD RED → GREEN → Pint → full suite).
No Fortify/Breeze/Jetstream (bring their own auth UI), no global settings table
(out of scope for now).

## Backend

Migration: add `users.avatar_path` (string, nullable).

Endpoints:

| Method | Path                    | Auth | Behavior |
|--------|-------------------------|------|----------|
| PATCH  | `/api/me`               | yes  | Update name + email (`unique:users,ignore:id`). Returns user. |
| POST   | `/api/me/avatar`        | yes  | Multipart `avatar` (jpg/png/webp, <=2MB). Store on local disk `avatars/{id}`; delete old file. Returns user. |
| GET    | `/api/me/avatar`        | yes  | Stream stored image (cache headers). 404 when none. |
| DELETE | `/api/me/avatar`        | yes  | Remove avatar. Returns user. |
| PUT    | `/api/me/password`      | yes  | Verify `current_password`; set new (`Password::defaults()`); revoke other Sanctum tokens. |
| POST   | `/api/forgot-password`  | no   | Always 200 (no enumeration). Email reset link via `Password` broker; URL points to SPA reset page. Throttled 5/min. |
| POST   | `/api/reset-password`   | no   | Token + email + new password; on success issue fresh Sanctum token. |

User JSON (auth responses) includes `avatar_url` (nullable) when set.

## Frontend

- `Settings.jsx` at `/settings`: **Profile** card (avatar preview, upload,
  remove, name/email, save) and **Security** card (change password). Refreshes
  cached user in `AuthContext`.
- `Login.jsx`: add "Forgot password?" link.
- New public pages: `ForgotPassword.jsx`, `ResetPassword.jsx` (reads `token` +
  `email` from URL). New routes in `App.jsx`.

## Error handling & security

- Validation → 422 with field errors; 401 unauthenticated; 404 missing avatar.
- Forgot-password does not reveal whether an email exists.
- Password change revokes other sessions; current session stays signed in.

## Tests

`tests/Feature/AccountSettingsTest.php`:
profile update; email-conflict 422; avatar upload/stream/delete + non-image
rejection; password change (wrong current → 422; other-token revocation);
forgot-password sends notification (`Notification::fake()`) and is throttled;
reset-password success + invalid-token 422.

Playwright: verify settings, forgot, and reset pages render and submit.