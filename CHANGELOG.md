# Changelog

All notable changes to this project will be documented in this file.

## 1.0.1 - 2026-11-24

### Fixes

- A failed reload is run again on the next sync, until it succeeds. Before, the next sync found the file up to date and returned, leaving nginx on the old list until the next change. (#2)

### Security

- Requires `jeffersongoncalves/laravel-scanner-guard` ^2.2.1, which sanitizes the free-text ban reasons this sink writes into comments. (#2)

### Upgrading

A reload command that always fails is now run, and logged, on every sync. A subclass overriding the protected `reload()` must return `bool`.

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-scanner-guard-nginx/compare/1.0.0...1.0.1

## 1.0.0 - 2026-11-19

First release. Requires jeffersongoncalves/laravel-scanner-guard ^2.0; see the README for setup.
