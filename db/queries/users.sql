-- name: GetUserByID :one
SELECT id, name, email, email_verified_at, timezone, week_start, current_team_id, is_placeholder
FROM users
WHERE id = $1
LIMIT 1;

-- name: GetUserByEmail :one
SELECT id, name, email, email_verified_at, timezone, week_start, current_team_id, is_placeholder, password
FROM users
WHERE email = $1
LIMIT 1;
