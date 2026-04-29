# Elonara API

`api.elonara.local` is the standalone shared backend boundary for Elonara services. It owns shared identity for the product family while Home, Social, and future products keep their own product databases separate.

The API is intentionally separate from Home and Social so shared backend behavior is exposed through HTTP endpoints instead of cross-product database access.

## Current Endpoints

### `GET /health`

Returns service health.

```json
{
  "status": "ok",
  "service": "elonara_api"
}
```

### `GET /db-check`

Returns database connectivity status.

Success:

```json
{
  "database": "connected"
}
```

Failure:

```json
{
  "database": "error",
  "message": "Database connection failed"
}
```

### `POST /identity/register`

Creates an API-owned identity user.

Input:

```json
{
  "email": "person@example.com",
  "password": "password"
}
```

Success:

```json
{
  "status": "created"
}
```

### `POST /identity/login`

Verifies an API-owned identity user and creates an API session.

Input:

```json
{
  "email": "person@example.com",
  "password": "password"
}
```

Success:

```json
{
  "token": "session-token",
  "expires_at": "YYYY-MM-DD HH:MM:SS"
}
```

### `GET /identity/me`

Returns the authenticated identity user for a bearer token.

Header:

```text
Authorization: Bearer session-token
```

Success:

```json
{
  "id": 1,
  "email": "person@example.com"
}
```

## Database

The current database is:

```text
elonara_api
```

Database credentials are loaded from:

```text
config/.env
```

Use `config/.env.example` as the template. Do not commit real credentials.

The current migration is:

```text
migrations/001_identity_foundation.sql
```

Current tables:

- `identity_users`
- `identity_sessions`
- `product_memberships`

## Boundary Rules

- The API owns shared identity.
- Home and Social must not connect directly to the `elonara_api` database.
- Product databases remain separate.

When integration begins, Home and Social should call API endpoints exposed by `api.elonara.local` instead of opening direct database connections to `elonara_api`.

## Integration Status

- The API boots through Apache.
- The database connection is verified.
- The API owns the initial identity users and sessions.
- Home is not connected yet.
- Social is not connected yet.
- Time and maps are not implemented yet.

## Next Planned Milestone

Product membership endpoints.
