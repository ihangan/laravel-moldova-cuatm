# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and the project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0]

Fixes the codes. v1.0.0 stored the wrong column of the classifier.

### Changed

- **Breaking.** `code` now holds the *codul unic de identificare* - the 4-sign
  CUATM identifier the classifier publishes for automated systems (Dobrogea
  `0112`). It previously held the 7-digit *cod statistic* (`0111001`), which is
  a separate numbering system: Băcioi is `0112000` statistically but `5511` in
  CUATM, Tiraspol `9801000` and `0700`. Anything joining on `code` against a
  government dataset was matching the wrong identifier.
- Corrected the statistical codes of mun. Chișinău, mun. Bălți and mun. Bender.
  They had been synthesised as `0100000` / `0300000` / `0500000`, values that
  appear nowhere in the classifier; the real ones are `0101000` / `0301000` /
  `0501000`.

- **Breaking.** The second-level units are now called districts, the English
  term ISO 3166-2:MD uses for them. `LocationType::Raion` is
  `LocationType::District` and its stored value went from `raion` to `district`;
  `Cuatm::raioane()` is `Cuatm::districts()`. The classifier's own wording,
  raion, survives only in the readme, where it is explained once.
- Romanian names are normalised to the comma-below diacritics ș/ț (U+0219 /
  U+021B). 588 of the 1721 names carried the Turkish cedilla ş/ţ (U+015F /
  U+0163) inherited from the typesetting of the classifier, which sorts and
  compares differently from the letters Romanian actually uses.
- The hand-checked names for the larger cities, the sectors of Chișinău and the
  special regions moved out of a const inside `ImportCuatmCommand` and into the
  dataset, as `name_en` and corrected `name_ru` / `name_uk`. The dataset is now
  the single source of every name.

### Removed

- **Breaking.** `LocationType::City`. The classifier has no such status - a
  locality is a municipality, a town or a village - and no row ever carried it.
- **Breaking.** The `locales` and `fallback_locale` config keys. Nothing read
  them - the fallback is set on spatie/laravel-translatable itself, as the
  readme describes.

### Added

- `LocationType::label()`, giving every type a reader-facing name in all four
  locales - `raion` in Romanian, `district` in English, `район` in Russian and
  Ukrainian. Override them with
  `vendor:publish --tag="moldova-cuatm-translations"`.
- `statistic_code` column, keeping the 7-digit statistical code, plus
  `Location::whereStatisticCode()` and `Cuatm::findByStatisticCode()`.
- An index on `parent_id`. `foreignId()->constrained()` creates the foreign key
  but not an index, and PostgreSQL and SQLite do not add one on their own, so
  reading a unit's children was a full scan on those drivers. The two indexes
  are now `(parent_id, sort_order)` and `(type, sort_order)`, which is what every
  read in the package actually asks for: filter on one, order by the other. They
  replace `(type, parent_id)` and the standalone `sort_order`, neither of which
  a query could use.

### Unchanged

The 1721 localities, their slugs, names, coordinates, types and the parent tree
are untouched. Parent links live on `parent_id`, never on the codes, so nothing
was re-parented - which matters now that `code` no longer encodes the hierarchy.

### Upgrading

```php
Schema::table('cuatm_locations', function (Blueprint $table): void {
    $table->string('statistic_code')->nullable()->unique()->after('code');

    $table->dropIndex(['type', 'parent_id']);
    $table->dropIndex(['sort_order']);
    $table->index(['parent_id', 'sort_order']);
    $table->index(['type', 'sort_order']);
});
```

Then `php artisan cuatm:import` to backfill both columns - it upserts on `slug`,
so existing rows keep their ids and their parents. Once it has run you can drop
the `nullable()`. If you stored `code` values elsewhere, they are now the
statistical codes and should be re-mapped through `statistic_code`; if you
stored the `raion` type value, it is now `district`.

## [1.0.0]

First release.

- `Location` Eloquent model covering the full CUATM hierarchy (raioane,
  municipalities, sectors, towns and villages, plus Gagauzia and Transnistria).
- Translatable names in Romanian, Russian and Ukrainian, with English filled in
  for the larger cities and falling back to Romanian elsewhere.
- The official CUATM code and the WGS84 coordinates for every locality.
- `cuatm:import` command that loads the bundled dataset, idempotently.
- `Cuatm` facade with the common lookups (by code, by slug, by type, the tree).
