#!/bin/bash
nix develop --extra-experimental-features "nix-command flakes" --command bash -c "dart pub get && source .env && dart run bin/main.dart"