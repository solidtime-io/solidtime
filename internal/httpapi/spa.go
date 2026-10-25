package httpapi

import (
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"html/template"
	"io/fs"
	"strings"
	"sync"

	"github.com/Dvorinka/tock/internal/web"
)

// viteManifest is the laravel-vite-plugin manifest format:
// { "resources/js/app.ts": {"file": "assets/app-HASH.js", "css": [...], "imports": [...]} }
type viteManifest map[string]struct {
	File    string   `json:"file"`
	CSS     []string `json:"css"`
	Imports []string `json:"imports"`
	IsEntry bool     `json:"isEntry"`
}

// spaHandler renders the Inertia HTML shell (the app.blade.php equivalent)
// with the vite entry tags and the page object in the #app div.
type spaHandler struct {
	once      sync.Once
	entryFile string
	entryCSS  []string
	version   string
	loadErr   error
}

// shell mirrors resources/views/app.blade.php: favicons, vite entry tags,
// the Ziggy route config, and the Inertia #app div.
var shellTemplate = template.Must(template.New("shell").Parse(`<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

        <title inertia>{{ .Title }}</title>

        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
        <link rel="manifest" href="/favicons/site.webmanifest">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#000000">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-TileColor" content="#000000">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#000000">

        <script>
            const Ziggy = {{ .Ziggy }};
        </script>
        {{- range .Styles }}
        <link rel="stylesheet" href="/build/{{ . }}">
        {{- end }}
        <script type="module" src="/build/{{ .Script }}"></script>
    </head>
    <body class="font-sans antialiased">
        <div id="app" data-page="{{ .Page }}"></div>
    </body>
</html>
`))

// ziggyJS holds the route config injected like @routes until the real Ziggy
// manifest endpoint exists (Phase 1).
var ziggyJS = template.JS(`{"url":"","port":null,"defaults":{},"routes":{}}`) //nolint:gochecknoglobals // shell constant

// inertiaPage is the Inertia protocol page object.
type inertiaPage struct {
	Component string         `json:"component"`
	Props     map[string]any `json:"props"`
	URL       string         `json:"url"`
	Version   string         `json:"version"`
}

// sharedProps mirrors the shared Inertia props from upstream
// (HandleInertiaRequests + ShareInertiaData) for an unauthenticated request.
func sharedProps() map[string]any {
	return map[string]any{
		"auth": map[string]any{
			"user":        map[string]any{},
			"permissions": []string{},
		},
		"errors":                  map[string]any{},
		"has_billing_extension":   false,
		"has_invoicing_extension": true,
		"has_services_extension":  false,
		"has_goals_extension":     true,
		"billing":                 nil,
		"flash": map[string]any{
			"message":     nil,
			"bannerText":  nil,
			"bannerStyle": nil,
		},
	}
}

func (s *spaHandler) load() {
	manifestBytes, err := fs.ReadFile(web.Dist, "dist/build/manifest.json")
	if err != nil {
		s.loadErr = fmt.Errorf("SPA assets not embedded (run `just spa`): %w", err)
		return
	}
	sum := sha256.Sum256(manifestBytes)
	s.version = hex.EncodeToString(sum[:8])

	var manifest viteManifest
	if err := json.Unmarshal(manifestBytes, &manifest); err != nil {
		s.loadErr = fmt.Errorf("parse vite manifest: %w", err)
		return
	}
	entry, ok := manifest["resources/js/app.ts"]
	if !ok {
		s.loadErr = fmt.Errorf("vite manifest: entry resources/js/app.ts not found")
		return
	}
	s.entryFile = entry.File
	s.entryCSS = entry.CSS
}

// renderPage renders the shell HTML for a given Inertia page.
func (s *spaHandler) renderPage(component, url string, props map[string]any) ([]byte, error) {
	s.once.Do(s.load)
	if s.loadErr != nil {
		return nil, s.loadErr
	}

	merged := sharedProps()
	for k, v := range props {
		merged[k] = v
	}
	page := inertiaPage{Component: component, Props: merged, URL: url, Version: s.version}
	pageJSON, err := json.Marshal(page)
	if err != nil {
		return nil, fmt.Errorf("marshal page: %w", err)
	}

	var out strings.Builder
	err = shellTemplate.Execute(&out, map[string]any{
		"Title":  "tock",
		"Ziggy":  ziggyJS,
		"Script": s.entryFile,
		"Styles": s.entryCSS,
		"Page":   string(pageJSON), // html/template attribute-escapes the JSON
	})
	if err != nil {
		return nil, fmt.Errorf("render shell: %w", err)
	}
	return []byte(out.String()), nil
}
