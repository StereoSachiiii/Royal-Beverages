# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Automated QA Pipeline (PHPStan, PHP CS Fixer, PHPUnit)
- GitHub Actions CI workflow to enforce 80% test coverage and PSR-12 styling
- Security scanning via `composer audit`
- Database migrations and optimizations (indexes, materialized views)

### Changed
- Refactored `Database` and `Session` singletons to use the existing Dependency Injection container
- Migrated legacy queries to use optimized Postgres schema features

### Fixed
- Session destruction bug during logout
- Stock reservation race conditions during order cancellation
- SQL syntax error in ProductRepository search logic
- Unchecked JSON serialization in PaymentRepository
