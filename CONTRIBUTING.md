# Contributing

Install development dependencies with `composer install`. Before submitting a
change, run:

```sh
composer qa
```

`composer qa` runs PHP CS Fixer, PHPStan, and PHPUnit.

Documentation checks validate local Markdown links, PHP syntax, and imported
types. The workflow tests execute examples against temporary fonts, check
their output, and reload generated font files. Keep these cases aligned with
user-visible results when changing examples.

For font output or subsetting changes, also run the independent
[sanitizer and shaping checks](tests/Validation/README.md):

```sh
composer validate-opentype
```

The validation guide lists the additional test dependencies. They are not
runtime dependencies of the PHP package.

Describe public behavior changes in the user guides and changelog. Keep
OpenType implementation details in the [support reference](docs/reference/opentype-subsetting.md)
or the validation notes.

To regenerate the documentation illustrations, see
[Figure sources and generation](docs/reference/documentation-figures.md). Check the
rendered SVGs as well as the measurements when updating a fixture.
