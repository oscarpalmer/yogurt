# Yogurt

[![PHP version](https://badge.fury.io/ph/oscarpalmer%2Fyogurt.png)](http://badge.fury.io/ph/oscarpalmer%2Fyogurt) [![Build Status](https://travis-ci.org/oscarpalmer/yogurt.png?branch=master)](https://travis-ci.org/oscarpalmer/yogurt) [![Coverage Status](https://coveralls.io/repos/oscarpalmer/yogurt/badge.png)](https://coveralls.io/r/oscarpalmer/yogurt)

Yogurt is a template language for PHP (`>=5.3`) inspired by [Riot's](http://riothq.com) [Hammer](http://hammerformac.com). Hammer's syntax was based on regular ol' HTML comment tags, so you won't have to install another syntax highlighter for your editor. Nice, huh?

## Getting started

### Installation

Yogurt is available via Composer.

```json
{
  "require": {
    "oscarpalmer/yogurt": "1.*"
  }
}
```

### Basic usage

```php
use oscarpalmer\Yogurt\Yogurt;

$yogurt = new Yogurt("./directory/for/templates", "template-extension");

$flavour = $yogurt->flavour("my-template");
# Or $flavour = new Flavour($yogurt, "my-template");

$flavour->data(array(
  "title" => "My Title"
));

$flavour->tagline = "My tagline.";

echo $flavour->taste();
# Or just echo $flavour;
```

## Syntax

The syntax is based on regular HTML comments and the control structures (`if` and `foreach`) are based on [Twig's](//github.com/fabpot/Twig) syntax, so it shouldn't be too difficult to learn.

### Variables

```html
<p><!-- variable --></p>
<p><!-- chaining.variables.works.too --></p>
```

`variable` is a direct child of the data object (`$flavour->data();`), and the `chaining.variables.works.too` is a nested child of multiple arrays or objects inside the data object.

Variables _should_ be of the `scalar` type, i.e. `boolean`, `float`, `integer`, or `string`. If not, PHP will scream.

### Variable modifiers

```html
<p><!-- variable ~ escape --></p>
```

Variables can be modified, too.

- `dump`: dumps the variable with `var_dump`.
- `escape`: escapes bad characters, e.g. tags.
- `json`: transforms anything into valid JSON markup.
- `lowercase`: converts the string to a lowercase version of it.
- `trim`: trims the string of leading and trailing whitespace.
- `uppercase`: converts the string to an uppercase version of it.

### Including other files

```html
<!-- include file.html -->
<!-- include file -->
```

Yogurt will then attempt to find the file in the directory supplied to Yogurt as shown in the ["Getting started" example](#getting-started); the second one will automatically append the supplied file extension.

### Looping

```html
<!-- for item in items -->
<p><!-- item --></p>
<!-- endfor -->
```

`items` _should_ be an array or object, but `item` can be whatever; just remember to chain names to access the item's children if it isn't `scalar`, like this: `item.title`.

#### Indexes

When looping, sometimes you need to know the index of the item – i.e. its place in the array or object. The item's index can be accessed by appending `_index` to the array or object's name, e.g. `array_index` for an array named `array`.

### Ifs and else-ifs

```html
<!-- if title -->
<p>Title exists.</p>
<!-- endif -->

<!-- if page is "some-page" -->
<p>Page is "some-page".</p>
<!-- elseif number === 1234 -->
<p>Number is "1234".</p>
<!-- else -->
<p>This is pretty cool.</p>
<!-- endif -->
```

Supported comparison operators are `===`, `==`, `!==`, `!=`, `>=`, `<=`, `<>`, `>`, `<`, `is`, and `isnt`. `is` and `isnt` will be turned into `==` and `!=` respectively.

Values can be `scalar`, `null`, or variables. `boolean`, `float`, `integer`, and `null` values can be wrapped in quotation marks, but don't necessarily need them.

Strings however, do need quotation marks; if they're not wrapped in quotation marks, they're assumed to be variables.

## Todo

- Update for PHP7, new PHPUnit, etc.
- More string modifiers.
- Adding and removing custom modifier functions.
- Adding and removing custom parsers functions?
- Caching?

## License

MIT Licensed; see [the LICENSE file](LICENSE) for more info.
