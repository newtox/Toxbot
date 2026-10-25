FROM dart:stable

RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-dev \
    sqlite3 \
    clang \
    llvm \
    lld \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY pubspec.yaml pubspec.lock ./
RUN dart pub get

COPY . .

ARG GIT_COMMIT
ARG BUILD_DATE
ENV GIT_COMMIT=$GIT_COMMIT \
    BUILD_DATE=$BUILD_DATE

CMD ["dart", "run", "bin/main.dart"]
