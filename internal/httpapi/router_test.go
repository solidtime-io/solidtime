package httpapi

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/Dvorinka/tock/internal/config"
)

func testRouter() http.Handler {
	cfg := config.Load()
	return NewRouter(cfg, nil)
}

func TestUsersMeRequiresAuth(t *testing.T) {
	rec := httptest.NewRecorder()
	testRouter().ServeHTTP(rec, httptest.NewRequest(http.MethodGet, "/api/v1/users/me", nil))

	if rec.Code != http.StatusUnauthorized {
		t.Fatalf("expected 401, got %d", rec.Code)
	}
	var body map[string]string
	if err := json.NewDecoder(rec.Body).Decode(&body); err != nil {
		t.Fatal(err)
	}
	if body["message"] != "Unauthenticated." {
		t.Fatalf("unexpected body: %v", body)
	}
}

func TestApiFallbackReturns404JSON(t *testing.T) {
	for _, path := range []string{"/api", "/api/v1/nonexistent", "/api/v1/users/me/api-tokens"} {
		rec := httptest.NewRecorder()
		testRouter().ServeHTTP(rec, httptest.NewRequest(http.MethodGet, path, nil))

		if rec.Code != http.StatusNotFound {
			t.Fatalf("%s: expected 404, got %d", path, rec.Code)
		}
		if ct := rec.Header().Get("Content-Type"); ct != "application/json" {
			t.Fatalf("%s: expected JSON content type, got %q", path, ct)
		}
	}
}

func TestRootRedirectsToLogin(t *testing.T) {
	rec := httptest.NewRecorder()
	testRouter().ServeHTTP(rec, httptest.NewRequest(http.MethodGet, "/", nil))

	if rec.Code != http.StatusFound {
		t.Fatalf("expected 302, got %d", rec.Code)
	}
	if loc := rec.Header().Get("Location"); loc != "/login" {
		t.Fatalf("expected redirect to /login, got %q", loc)
	}
}

func TestUnknownPageRedirectsToLogin(t *testing.T) {
	rec := httptest.NewRecorder()
	testRouter().ServeHTTP(rec, httptest.NewRequest(http.MethodGet, "/dashboard", nil))

	if rec.Code != http.StatusFound {
		t.Fatalf("expected 302, got %d", rec.Code)
	}
}
