#!/bin/bash
cd "$(dirname "$0")"
nix develop .. --extra-experimental-features "nix-command flakes" --command dart run bin/main.dart
