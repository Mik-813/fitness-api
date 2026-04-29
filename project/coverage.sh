#!/bin/bash

set -e

echo "Running tests and generating HTML coverage report..."

XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage-report

echo "✅ Coverage report successfully generated! Open coverage-report/index.html in your browser to view it."