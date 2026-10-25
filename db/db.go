// Package db exposes the goose SQL migrations as an embedded filesystem.
package db

import "embed"

// Migrations contains the goose migrations in db/migrations.
//
//go:embed migrations/*.sql
var Migrations embed.FS
