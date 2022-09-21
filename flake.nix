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
    dream2nix = {
      url = "github:nix-community/dream2nix";
    };
  };

  outputs = { self, nixpkgs, flake-compat, dream2nix }:
    let
      # System types to support.
      supportedSystems = [ "x86_64-linux" "x86_64-darwin" "aarch64-linux" "aarch64-darwin" ];
      # Helper function to generate an attrset '{ x86_64-linux = f "x86_64-linux"; ... }'.
      forAllSystems = nixpkgs.lib.genAttrs supportedSystems;
      # Nixpkgs instantiated for supported system types.
      nixpkgsFor = forAllSystems (system: import nixpkgs { inherit system; });

      # use php 8.1 with these extensions
      php = forAllSystems (system:
        nixpkgsFor.${system}.php81.withExtensions (
          { all, enabled }: with all; enabled ++ [
            xdebug
          ]
        ));

      # configure php ini settings
      phpConfigured = forAllSystems (system: php.${system}.buildEnv {
        extraConfig = ''
          [php]
          upload_max_filesize = 20M
          memory_limit = 2048M
          max_input_vars = 5000
          upload_tmp_dir = /tmp
          realpath_cache_size = 12288K
          realpath_cache_ttl = 1800
          [session]
          session.cookie_secure = On
          session.cookie_httponly = On
          session.use_strict_mode = On
          session.cookie_samesite = Strict
          session.cookie_lifetime = 2000000
          session.gc_divisor = 100
          session.gc_maxlifetime = 200000
          session.gc_probability = 1
          [xdebug]
          xdebug.mode = debug,profile
          xdebug.output_dir = /tmp
          xdebug.start_with_request = trigger
          xdebug.client_port = 9003
          xdebug.client_host = localhost
          xdebug.profiler_output_name = xdebug_profiler.out.%t
          [date]
          date.timezone = Europe/Amsterdam
        '';
      });

      dream2NixOutputs = dream2nix.lib.makeFlakeOutputs {
        systems = supportedSystems;
        config.projectRoot = ./.;
        source = ./.;
        settings = [ ];
      };
    in
    {
      packages = dream2NixOutputs.packages;
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

      devShells = dream2NixOutputs.devShells;

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
