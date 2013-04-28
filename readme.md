# Yogurt

## What is Yogurt?

Delicious. It's also a tiny templating engine for those who don't wish to use a big and clunky -- read "awesome" -- template engine like [Twig](//github.com/fabpot/Twig).

### Why?

Like I said, I disliked adding Twig to every little project that used templates. Twig is great, but it rarely fits the scope of my projects.

## How?

The best way to learn how Yogurt works is to check out the example in [test](test). But if you want, you can read about the various Yogurt tags below and how they work.

### Tags

#### Variables

`<!-- $variable -->`; where $variable is "Yogurt" renders as `Yogurt`.

#### If statements

`<!-- if $variable is "Yogurt" -->Yes!<!-- endif -->`; renders "Yes!" if `$variable` is "Yogurt".

The operator can also be `isnt`, `==`, and `!=` instead of `is`.

#### Includes

`<!-- @include file.html -->`; reads the file and renders its content.

#### Loops

`<!-- @loop $list ~ $item --><!-- $item --><!-- endloop -->`; runs a foreach loop on `$list` where direct descendants are called `$item`. Renders the block within the loop tags.

#### Errors

Errors are rendered "silently" as comments. Why? Because things that _can_ render _should_ render.

## Todo

+ Better error handling -- i.e. more errors
+ Nested tags
+ JavaScript version
+ Ruby version

You are -- as always -- more than welcome to suggest additions and improvements. :blush:

## Thanks to...

... [Riot](//riothq.com/) for making [Hammer](//hammerformac.com/) where the inspiration for the syntax came. I'm using Hammer daily for my static experiments and hacks, and it's the best way to build simple static sites.