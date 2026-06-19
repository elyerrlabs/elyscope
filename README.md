# Elyscope

PHP dependency isolation and namespace scoping tool for modular applications.

Elyscope is a core component of the ElyMod ecosystem and works alongside OAuth2 Passport Server to enable true application modularization in PHP.

## Why Elyscope?

Traditional Composer projects share a single `vendor` directory. This becomes a problem when building modular systems because modules may:

- Require different versions of the same package.
- Introduce dependency conflicts.
- Pollute the application's global namespace.
- Break compatibility between modules.
- Become difficult to distribute independently.

Elyscope solves these problems by creating isolated and scoped dependencies for each module.

Using PHP-Scoper and an automated Composer workflow, Elyscope generates a dedicated dependency layer that can coexist with the host application without namespace collisions.

### What problems does it solve?

- Dependency version conflicts.
- Namespace collisions.
- Vendor pollution.
- Package compatibility issues.
- Module portability limitations.
- Shared dependency management problems.

### ElyMod Ecosystem

Elyscope is an essential component of:

- ElyMod
- OAuth2 Passport Server

Together they provide the foundation required to build modular PHP and Laravel applications where each module can manage its own dependencies without affecting the host application.

---

## Features

- Dependency isolation using PHP-Scoper
- Automatic namespace prefixing
- Clean vendor backup management
- Scoped vendor generation
- Composer command passthrough
- Automatic installed.json synchronization
- Compatible with existing Composer workflows
- Designed for modular PHP applications
- Supports independent module distribution

---

## Installation

### Global Installation

Install ElyScope globally using Composer:

```bash
composer global require elyerr/elyscope
```

Verify the installation:

```bash
elyscope --help
```

### Command Not Found?

If `elyscope` is not recognized, Composer's global bin directory may not be available in your system `PATH`.

Display the Composer global bin directory:

```bash
composer global config bin-dir --absolute
```

You can run ElyScope directly using the full path returned by the command above:

```bash
/path/to/composer/bin/elyscope --help
```

Common locations include:

```bash
~/.config/composer/vendor/bin/elyscope --help
```

or

```bash
~/.composer/vendor/bin/elyscope --help
```

Alternatively, you can execute ElyScope through PHP without modifying your `PATH`:

```bash
php "$(composer global config bin-dir --absolute)/elyscope" --help
```

### Add Composer Bin Directory to PATH

For a permanent solution, add Composer's global bin directory to your system `PATH`.

Example for Bash:

```bash
export PATH="$(composer global config bin-dir --absolute):$PATH"
```

Add the command above to your `~/.bashrc`, `~/.zshrc`, or shell configuration file.

### Create a Symbolic Link

You can also create a symbolic link to make the command available system-wide:

```bash
sudo ln -s "$(composer global config bin-dir --absolute)/elyscope" /usr/local/bin/elyscope
```

Verify that the link works:

```bash
elyscope --help
```

---

## Usage

### Install dependencies

```bash
elyscope install
```

Workflow:

1. Remove existing vendor directory
2. Run Composer install
3. Run PHP-Scoper
4. Create vendor-packsave backup
5. Replace vendor with scoped vendor-build
6. Update installed.json metadata
7. Generate optimized autoload files

---

### Production install

```bash
elyscope install --no-dev
```

---

### Update dependencies

```bash
elyscope update
```

Workflow:

1. Restore vendor from vendor-packsave
2. Run Composer update
3. Refresh vendor-packsave
4. Run PHP-Scoper
5. Replace vendor with scoped vendor-build
6. Update installed.json metadata
7. Generate optimized autoload files

---

### Production update

```bash
elyscope update --no-dev
```

---

## Composer Passthrough

Commands not handled by Elyscope are automatically forwarded to Composer.

Examples:

```bash
elyscope require monolog/monolog
```

```bash
elyscope remove monolog/monolog
```

```bash
elyscope show
```

```bash
elyscope outdated
```

```bash
elyscope validate
```

---

## Directory Structure

```text
vendor/
vendor-packsave/
vendor-build/
```

### vendor

Scoped dependencies used by the module.

### vendor-packsave

Clean backup of the original Composer dependencies before scoping.

### vendor-build

Temporary PHP-Scoper output directory.

---

## Requirements

- PHP 8.2+
- Composer
- PHP-Scoper

---

## License

Copyright (C) 2026 Elvis Y. Roman C.

Licensed under the GNU Affero General Public License v3.0 or later.
