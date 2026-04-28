# Elonara API

`api.elonara.local` is the standalone shared backend boundary for Elonara services. It is intentionally separate from Home and Social so shared backend behavior can be added behind API endpoints before calendar, tasks, maps, or identity logic is integrated.

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

## Development Database

The current development database is:

```text
elonara_api
```

Database credentials are loaded from:

```text
config/.env
```

Use `config/.env.example` as the template. Do not commit real credentials.

## Boundary Rules

Home and Social must not connect directly to the `elonara_api` database.

When integration begins, Home and Social should call API endpoints exposed by `api.elonara.local` instead of opening direct database connections to `elonara_api`.

## Current Status

- The API boots through Apache.
- The database connection is verified.
- Identity, time, and maps are not implemented yet.

## Next Planned Milestone

Add the initial identity schema:

- `identity_users`
- `identity_credentials`
- `identity_sessions`
- `product_memberships`
