# Introduction

This is a very simple native PHP template engine based on the ideas as found in 
[Plates](http://platesphp.com/). The templates are mostly compatible with 
Plates templates, so it is easy to switch.

The following features are not (yet) part of Plates, but hopefully are some 
time in the future:

- Themes, this is not working 
  [properly](https://github.com/thephpleague/plates/issues/234) in Plates;
- Multi Language support

If you need neither of these features, I recommend you to use Plates.

# Example

You can define a "layout", say in `base.php`, and various templates that 
"inherit" this template.

For example, `base.php` contains this:

    <!DOCTYPE html>
    <html>
        <head>
            <title><?=$this->e($title)?></title>
        </head>
        <body>
        <div class="content">
            <?=$this->section('content')?>
        </div>
        </body>
    </html>

Then, for example the template `page.php` contains this:

    <?php $this->layout('base', ['title' => 'Page'])?>
    <?php $this->start('content') ?>
        <p>
            This is the content that will be placed in the "base.php" template 
            in the "content" section.
        </p>
        <ul>
            <?php foreach($simpleList as $listEntry): ?>
                <li><?=$this->e($listEntry)?></li>
            <?php endforeach ?>
        </ul>
    <?php $this->stop('content') ?>

You'd render the template like this:

    <?php
    use fkooman\Template\Tpl;

    $tpl = new Tpl(
        [
            '/path/to/templates'
        ]
    );
    echo $tpl->render('page', ['simpleList' => ['a', 'b', 'c']);

# Themes

The path to the constructor is an array which can contain multiple folders. 
The first folder points to the "base" template. Additional folders can 
override specific templates (or all of them).

    <?php
    use fkooman\Template\Tpl;

    $tpl = new Tpl(
        [
            '/path/to/templates',
            '/path/to/my_theme',
        ]
    );

The library will first check the `my_theme` folder for templates, if they are 
missing there, the search will continue in the preceding folder(s).

# Translations

    <?php
    use fkooman\Template\Tpl;

    $tpl = new Tpl(['/path/to/templates'], ['/path/to/locale']);
    $tpl->setLanguage('nl-NL');
    $tpl->render('foo', ['foo' => 'bar']);

In the template you use this:

    <?=$this->t('Hello %foo%!')?>

The translation file, i.e. `nl-NL.php` in this example contains this:

    <?php

    return [
        'Hello %foo%!' => 'Hallo %foo%!'
    ];

You can specify multiple translation folder as well. The last one specified 
has preference.

# Contact

You can contact me with any questions or issues regarding this project. Drop
me a line at [fkooman@tuxed.net](mailto:fkooman@tuxed.net).

If you want to (responsibly) disclose a security issue you can also use the
PGP key with key ID `9C5EDD645A571EB2` and fingerprint
`6237 BAF1 418A 907D AA98  EAA7 9C5E DD64 5A57 1EB2`.

# License

[MIT](LICENSE).
