#!/bin/sh
set -e

# ---------------------------------------------------------------------------- #
#
# Installs The coder library so we can use t for code reviews.
#
# ---------------------------------------------------------------------------- #

# Check the current build.
if [ -z "${CODE_REVIEW+x}" ] || [ "$CODE_REVIEW" -ne 1 ]; then
 exit 0;
fi

composer global require hirak/prestissimo
composer global require drupal/coder phpcompatibility/php-compatibility jakub-onderka/php-parallel-lint jakub-onderka/php-console-highlighter
phpcs --config-set installed_paths ${VENDOR_DIR}/drupal/coder/coder_sniffer,${VENDOR_DIR}/phpcompatibility/php-compatibility
