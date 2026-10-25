// Package httpapi wires the chi router: /api JSON surface plus the embedded
// SPA with the Inertia shell for page routes.
package httpapi

import (
	"encoding/json"
	"io/fs"
	"log/slog"
	"net/http"
	"strings"
	"time"

	"github.com/Dvorinka/tock/internal/config"
	"github.com/Dvorinka/tock/internal/web"
	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
	"github.com/jackc/pgx/v5/pgxpool"
)

// NewRouter builds the HTTP handler for `tock serve`.
func NewRouter(cfg config.Config, pool *pgxpool.Pool) http.Handler {
	_ = pool // used once real handlers land
	r := chi.NewRouter()

	r.Use(middleware.RequestID)
	r.Use(middleware.Recoverer)
	r.Use(requestLogger(cfg.Logger))

	r.Route("/api", func(r chi.Router) {
		r.Get("/", apiNotFound)

		r.Route("/v1", func(r chi.Router) {
			// Public endpoints.
			r.Get("/currencies", apiNotImplemented)
			r.Get("/time-zones", apiNotImplemented)
			r.Get("/public/reports", apiNotImplemented)

			// Everything under /api/v1 outside the public set sits behind
			// auth:api + verified upstream. Until the token guard lands in
			// Phase 1, all authenticated routes answer 401 like Laravel does.
			r.Get("/users/me", unauthenticated)
		})

		r.NotFound(apiNotFound)
		r.MethodNotAllowed(apiNotFound)
	})

	spa := &spaHandler{}
	r.Get("/", func(w http.ResponseWriter, r *http.Request) {
		http.Redirect(w, r, "/login", http.StatusFound)
	})
	r.Get("/login", func(w http.ResponseWriter, r *http.Request) {
		writeInertiaPage(w, r, spa, "Auth/Login", "/login", map[string]any{
			"canResetPassword": true,
			"status":           nil,
		})
	})
	mountStatic(r)
	r.NotFound(spaFallback(spa))

	return r
}

func writeJSON(w http.ResponseWriter, status int, body any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(body)
}

func unauthenticated(w http.ResponseWriter, _ *http.Request) {
	writeJSON(w, http.StatusUnauthorized, map[string]string{"message": "Unauthenticated."})
}

func apiNotFound(w http.ResponseWriter, _ *http.Request) {
	writeJSON(w, http.StatusNotFound, map[string]string{"message": "API resource not found"})
}

// apiNotImplemented marks routes that exist upstream but are not ported yet.
func apiNotImplemented(w http.ResponseWriter, _ *http.Request) {
	writeJSON(w, http.StatusNotFound, map[string]string{"message": "API resource not found"})
}

func writeInertiaPage(w http.ResponseWriter, r *http.Request, spa *spaHandler, component, url string, props map[string]any) {
	page, err := spa.renderPage(component, url, props)
	if err != nil {
		slog.Error("render SPA shell", "error", err)
		writeJSON(w, http.StatusInternalServerError, map[string]string{"message": "Internal server error"})
		return
	}
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Vary", "X-Inertia")
	_, _ = w.Write(page)
}

// mountStatic serves the embedded public webroot (/build, /favicons, ...).
func mountStatic(r chi.Router) {
	sub, err := fs.Sub(web.Dist, "dist")
	if err != nil {
		return
	}
	fileServer := http.FileServer(http.FS(sub))
	for _, prefix := range []string{
		"/build/", "/favicons/", "/images/", "/fonts/", "/desktop-version/",
	} {
		p := prefix
		r.Get(p+"*", func(w http.ResponseWriter, r *http.Request) {
			fileServer.ServeHTTP(w, r)
		})
	}
	for _, file := range []string{"/favicon.ico", "/robots.txt", "/security.txt"} {
		f := file
		r.Get(f, func(w http.ResponseWriter, r *http.Request) {
			fileServer.ServeHTTP(w, r)
		})
	}
}

// spaFallback redirects unauthenticated page requests to /login, matching
// upstream's auth middleware behavior.
func spaFallback(spa *spaHandler) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodGet {
			writeJSON(w, http.StatusNotFound, map[string]string{"message": "Not found"})
			return
		}
		if strings.HasPrefix(r.URL.Path, "/api/") {
			apiNotFound(w, r)
			return
		}
		http.Redirect(w, r, "/login", http.StatusFound)
	}
}

// requestLogger logs one slog line per request.
func requestLogger(log *slog.Logger) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			start := time.Now()
			ww := middleware.NewWrapResponseWriter(w, r.ProtoMajor)
			next.ServeHTTP(ww, r)
			log.Info("request",
				"method", r.Method,
				"path", r.URL.Path,
				"status", ww.Status(),
				"bytes", ww.BytesWritten(),
				"duration_ms", time.Since(start).Milliseconds(),
				"request_id", middleware.GetReqID(r.Context()),
			)
		})
	}
}
