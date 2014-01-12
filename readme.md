# Yogurt

## What is Yogurt?

Delicious. It's also a tiny templating engine for those who don't wish to use a big and clunky - read "awesome" - template engine like [Twig](//github.com/fabpot/Twig).

### Why?

Like I said, I disliked adding Twig to every little project that used templates. Twig is great, but it rarely fits the scope of my projects.

## How?

The best way to learn how Yogurt works is to check out the example in [test](test). But if you want, you can read about the various Yogurt tags below and how they work.

### Variables

`<!-- $variable -->`; renders the value of `$variable`.

### Includes

`<!-- include partial.html -->`

Reads, parses, and renders the included file if the file exists.

### Foreach loops

```html
<!-- $list : $item -->
  <p>Loop!</p>
<!-- $list; -->
```

Renders the contained block for all direct descendants of `$list`.

### If-statements

```html
<!-- if $variable is $variable -->
  <p>Yay!</p>
<!-- endif -->

<!-- if $variable isnt "value" -->
  <p>Yay?</p>
<!-- endif -->
```

Renders the contained block if the statements is true.

#### Else if

```html
<!-- else if $variable is $variable -->
  <p>Yay!</p>
<!-- endif -->

<!-- else if $variable isnt "value" -->
  <p>Yay?</p>
<!-- endif -->
```

Renders the contained block if the statements is true and if the statement is used after a regular if or another else-if statement; see line 47-51 in [the template](test/template.html).

#### If-exists

```html
<!-- if $variable -->
  <p>Yay!</p>
<!-- endif -->

<!-- else if $variable -->
  <p>Yay!</p>
<!-- endif -->
```

Renders the contained block if the variable `$variable` exists. The second statement should be used after a regular if or else-if statement.

## Errors

Errors are rendered "silently" as comments. Why? Because things that _can_ render _should_ render.

## Todo

- OOP.
- Tests.
- Caching.
- JavaScript version.
- Ruby version.

You are - as always - more than welcome to suggest additions and improvements. :blush:

## Thanks to...

... [Riot](http://riothq.com/) for making [Hammer](http://hammerformac.com/) where the inspiration for the syntax came. I'm using Hammer daily for my static experiments and hacks, and it's the best way to build simple static sites.