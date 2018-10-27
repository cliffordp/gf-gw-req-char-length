# GF_GW_Req_Char_Length

Require a minimum and/or maximum character length for specific Gravity Forms fields, displaying an informative Gravity Form validation error to the user for disallowed entries.

## Example Usage of this Class

***See https://gist.github.com/cliffordp/551eb4f67b8db8e19d3d59a0f2b7a6f9 for several examples.***

### Example Composer Usage

Here's an example of what you might want to put in your own project's `composer.php`:

```
{
	"require": {
		"gf-gw-req-char-length/gf-gw-req-char-length": ">=2.0.1 <3.0"
	},
	"minimum-stability": "stable"
}
```

In plain English:
* use version 2.0.1 or greater, as long as it is below version 3.0
* only use versions that are official Releases in the project's repo

*Pro tip: Installing this class via Composer allows you to do autoloading.*

Visit https://getcomposer.org/ to learn more about Composer. It's great!

### Credits

Of course, a big hat tip to Gravity Wiz:
* This repo began as a forked from the May 30, 2014, version of https://gist.github.com/spivurno/8220561
* Its accompanying article: https://gravitywiz.com/require-minimum-character-limit-gravity-forms/

## Changelog

#### Version 2.0.1: October 26, 2018

* Fix to avoid applying character length minimums to non-required fields that have no input.

#### Version 2.0.0: October 26, 2018

* Pretty much fully rewritten.
* Now requires Gravity Forms version 2.3+ (an arbitrarily-chosen recent version where `GFForms::$version` is used instead of the now-deprecated `GFCommon::$version`). Current GF version at time of this release is 2.3.6.
* Now requires PHP 5.4+ (uses array short syntax).
* Changed license from GPLv2+ to GPLv3+.
* Renamed class to better describe actual functionality, as it can be used for minimum and/or maximum.
* `'field_id'` argument now supports array type fields, such as Name and Address fields. (e.g. require Address Line 1 to be 5-30 characters long)
* `'field_id'` argument now supports passing an array to apply the same rules and messaging to multiple fields at once. (e.g. same length and messaging to First Name and Last Name)
* Added `composer.php`
* Added multiple new examples to demonstrate available functionality, but removed them from this file in case this class is added to your project via Composer. **See them at https://gist.github.com/cliffordp/551eb4f67b8db8e19d3d59a0f2b7a6f9**

#### Version 1.0.0: May 30, 2014

* Started by setting the May 30, 2014, version of https://gist.github.com/spivurno/8220561 as Version 1.0.0.