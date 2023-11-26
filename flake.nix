{
  description = "PHP Decoder Library";

  inputs = {
    nixpkgs = {
      url = "github:NixOS/nixpkgs";
    };
    flake-compat = {
      url = "github:edolstra/flake-compat";
      flake = false;
    };
  };

  outputs = { self, nixpkgs, flake-compat }:
    let
      # System types to support.
      supportedSystems = [ "x86_64-linux" "x86_64-darwin" "aarch64-linux" "aarch64-darwin" ];
      # Helper function to generate an attrset '{ x86_64-linux = f "x86_64-linux"; ... }'.
      forAllSystems = nixpkgs.lib.genAttrs supportedSystems;
      # Nixpkgs instantiated for supported system types.
      nixpkgsFor = forAllSystems (system: import nixpkgs { inherit system; });

      # use php 8.3 with these extensions
      php = forAllSystems (system:
        nixpkgsFor.${system}.php83.withExtensions (
          { all, enabled }: with all; enabled ++ [
            xdebug
          ]
        ));

      # configure php ini settings
      phpConfigured = forAllSystems (system: php.${system}.buildEnv {
        extraConfig = ''
          [php]
          memory_limit = 2048M
          [xdebug]
          xdebug.mode = debug,profile
          xdebug.output_dir = /tmp
          xdebug.start_with_request = trigger
          xdebug.client_port = 9003
          xdebug.client_host = localhost
          xdebug.profiler_output_name = xdebug_profiler.out.%t
        '';
      });
    in
    {
      # defaultPackage.x86_64-linux = self.packages.x86_64-linux.decoder;

      # Shell is run by `nix develop`
      devShell = forAllSystems (system:
        let
          pkgs = nixpkgsFor.${system};
        in
        pkgs.mkShell {
          buildInputs = [
            phpConfigured.${system}
            phpConfigured.${system}.packages.composer
          ];
          shellHook = "";
        });

      # devShells = dream2NixOutputs.devShells;

      # Executed by `nix flake check`
      # checks."<system>"."<name>" = derivation;
      # # Executed by `nix build .#<name>`
      # packages."<system>"."<name>" = derivation;
      # # Executed by `nix build .`
      # defaultPackage."<system>" = derivation;
      # # Executed by `nix run .#<name>`
      # apps."<system>"."<name>" = {
      #   type = "app";
      #   program = "<store-path>";
      # };
      # # Executed by `nix run . -- <args?>`
      # defaultApp."<system>" = { type = "app"; program = "..."; };
    };
}
