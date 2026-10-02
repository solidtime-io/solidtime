FROM golang:1.24-alpine AS build

ENV CGO_ENABLED=0

RUN go install -ldflags="-s -w" github.com/minio/mc@RELEASE.2025-08-13T08-35-41Z
RUN go install -ldflags="-s -w" github.com/minio/minio@RELEASE.2025-10-15T17-29-55Z

FROM alpine:3.22

RUN apk add --no-cache ca-certificates

COPY --from=build /go/bin/minio /go/bin/mc /usr/bin/

CMD ["minio", "server", "/data/minio"]
