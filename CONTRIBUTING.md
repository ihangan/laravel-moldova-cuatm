# Contributing

Thanks for taking the time to contribute.

## Reporting bugs and ideas

Open an issue. For bugs, include the package version, your Laravel and PHP versions, and a
short snippet that reproduces the problem. For data corrections (a wrong name, a bad
coordinate, a missing locality), point to the official CUATM entry so it can be checked.

## Pull requests

- Branch off `main`.
- Keep the change focused. One concern per pull request.
- Match the existing style. The code is strict-typed and analysed at PHPStan level max.
- Add or update tests for anything you change.

Before pushing, run the checks:

```bash
composer test       # PHPUnit
composer analyse    # PHPStan
composer format     # Pint
```

CI runs the same checks against Laravel 12 and 13 on PHP 8.3 and 8.4, so a green local run
usually means a green pipeline.

## Updating the dataset

The data lives in `database/data/cuatm.json`. When the National Bureau of Statistics
publishes a new CUATM edition, the file is regenerated from it. If you are submitting a data
fix by hand, change only the affected rows and keep the existing shape.
