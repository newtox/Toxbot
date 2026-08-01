{
  description = "Toxbot Discord Bot";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
  };

  outputs = { self, nixpkgs, ... }:
    let
      system = "x86_64-linux";
      pkgs = import nixpkgs { inherit system; };
    in {
      devShells.${system}.default = pkgs.mkShell {
        buildInputs = [
          pkgs.dart
        ];

        shellHook = ''
          echo "Loading dependencies via pub get..."
          dart pub get
          echo "Toxbot Discord Bot ready at v.($(dart --version))!"
        '';
      };
    };
}