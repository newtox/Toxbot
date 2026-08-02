FROM dart:stable

RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev sqlite3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY pubspec.yaml pubspec.lock ./
RUN dart pub get

COPY . .

ENV DART_INCLUDE_SECRETS=true

CMD ["dart", "bin/main.dart"]