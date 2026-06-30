# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and the project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0]

First release.

- `Location` Eloquent model covering the full CUATM hierarchy (raioane,
  municipalities, sectors, towns and villages, plus Gagauzia and Transnistria).
- Translatable names in Romanian, Russian and Ukrainian, with English filled in
  for the larger cities and falling back to Romanian elsewhere.
- The official CUATM code and the WGS84 coordinates for every locality.
- `cuatm:import` command that loads the bundled dataset, idempotently.
- `Cuatm` facade with the common lookups (by code, by slug, by type, the tree).
