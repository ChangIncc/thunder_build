
                                   ()
  ┌───────┐                        /\
  │       │                   ()--'  '--()
  │  a:o  │  acolono.com        `.    .'       Shariff Module
  │       │                      / .. \
  └───────┘                     ()'  '()


This module implements the Shariff sharing buttons by heise online:
https://github.com/heiseonline/shariff

Shariff enables website users to share their favorite content without
compromising their privacy.

It consists of two parts: a simple JavaScript client library and an
optional server-side component. The latter fetches the number of likes,
tweets and plus-ones.

The base shariff Drupal module implements the JavaScript library to
display the buttons as a block and a pseudo field.


-- REQUIREMENTS --

* Shariff Library (at least v1.4.6)
  https://github.com/heiseonline/shariff

-- INSTALLATION Standard --

1) Download the Drupal shariff module and place it in your modules folder.

2) Download the library from https://github.com/heiseonline/shariff and place
it in the Drupal root libraries folder.
So the JavaScript and the CSS files should be available under
"DRUPAL_ROOT/libraries/shariff/build/shariff.min.js",
"DRUPAL_ROOT/libraries/shariff/build/shariff.min.css" and
"DRUPAL_ROOT/libraries/shariff/build/shariff.complete.css".

You only need the build folder and at least v1.4.6 of the library.

-- INSTALLATION using Composer Manager --

Prerequisite: Composer Manager is installed and initialised. For more information see
https://www.drupal.org/node/2405811

1) Download the module.

2) Run "composer drupal-update" from the root of your Drupal directory. The shariff library will be downloaded into
your "DRUPAL_ROOT/libraries" folder.

-- INSTALLATION using Composer and wikimedia/composer-merge-plugin --

See https://www.drupal.org/node/2770807

-- CONFIGURATION --

1) Activate the module.

2) Set your default settings under /admin/config/services/shariff. When you
have Font Awesome already loaded on your site be sure to choose the Minimal
CSS option (so that shariff.min.css without Font Awesome will be loaded).

3) Now you can add the buttons as a block or as a field. Just click on "Place block" on the block layout overview page.
The field is available under "Manage Display" in your content type settings.
