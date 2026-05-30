$ErrorActionPreference = "Stop"

echo "=== Running Composer Validate ==="
php composer.phar validate --strict

echo "=== Running Composer Audit ==="
php composer.phar audit

echo "=== Running PHP Linting ==="
php composer.phar lint

echo "=== Running PHP Static Analysis ==="
php composer.phar analyze

echo "=== Running Tests ==="
php composer.phar test -- --coverage-clover coverage.xml

echo "=== Enforcing Code Coverage ==="
php -r "
    \$xml = simplexml_load_file('coverage.xml');
    \$metrics = \$xml->project->metrics;
    if ((int)\$metrics['elements'] == 0) {
        echo 'No tests found!\n';
        exit(1);
    }
    \$coverage = ((int)\$metrics['coveredelements'] / (int)\$metrics['elements']) * 100;
    echo 'Total Code Coverage: ' . number_format(\$coverage, 2) . '%\n';
    if (\$coverage < 80) {
        echo 'Error: Code coverage is below 80%.\n';
        exit(1);
    }
"

echo "=== Running NPM Install ==="
npm ci

echo "=== Running JS Linting ==="
npm run lint

echo "=== Running CSS Build ==="
npm run build:css

echo "=== CI Passed Successfully! ==="
