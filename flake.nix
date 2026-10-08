{
  description = "Toxbot Discord Bot";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
  };

  outputs = { self, nixpkgs, ... }:
    let
      system = "x86_64-linux";
      pkgs = import nixpkgs { inherit system; };
      php = pkgs.php84;
      root = ''"$(git rev-parse --show-toplevel 2>/dev/null || pwd)"'';
    in {
      devShells.${system} = {
        default = pkgs.mkShell {
          buildInputs = [ pkgs.dart ];

          shellHook = ''
            cd ${root}/bot
            dart pub get
            echo "Toxbot: $(dart --version 2>&1)"
          '';
        };

        dashboard = pkgs.mkShell {
          buildInputs = [
            php
            php.packages.composer
            pkgs.nodejs_22
            pkgs.mariadb.client
          ];

          shellHook = ''
            cd ${root}/dashboard
            echo "Toxbot Dashboard: PHP $(php -r 'echo PHP_VERSION;'), Node $(node -v)"
          '';
        };
      };
    };
}
