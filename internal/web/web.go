// Package web embeds the built SPA assets (Vite output + public/ files),
// assembled into dist/ by `just spa`.
package web

import "embed"

// Dist mirrors the public web root: /build, /favicons, /images, /fonts,
// /desktop-version, /favicon.ico, /robots.txt, /security.txt.
//
//go:embed all:dist
var Dist embed.FS
