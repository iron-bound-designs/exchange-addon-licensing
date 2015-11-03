# Licensing add-on for iThemes Exchange

[![Build Status](https://magnum.travis-ci.com/iron-bound-designs/exchange-addon-licensing.svg?token=pfFazQh7W5eMQVveDHSd&branch=master)](https://magnum.travis-ci.com/iron-bound-designs/exchange-addon-licensing) [![codecov.io](http://codecov.io/github/iron-bound-designs/exchange-addon-licensing/coverage.svg?branch=master&token=l8Gr12jRC3)](http://codecov.io/github/iron-bound-designs/exchange-addon-licensing?branch=master)

Licensing for iThemes Exchange allows you to sell license keys for your digital products. Licensing is currently in beta.

Licensing will be a commercial plugin available from Iron Bound Designs. The plugin is hosted here on a public Github repository 
in order to better faciliate community contributions from developers and users alike. If you have a suggestion, a bug report, 
or a patch for an issue, feel free to submit it here. We do ask, however, that if you are using the plugin on a live site 
that you please purchase a valid license from the [website](https://ironbounddesigns.com/plugins). 
We cannot provide support to anyone that does not hold a valid license key.

## Installation

### Stable

Download the latest version from the [Releases](https://github.com/iron-bound-designs/exchange-addon-licensing/releases)
tab on GitHub.

### Trunk

Licensing uses composer for dependency management. After cloning the repository run the following command:

```
composer install --no-dev
```

## Demo Data

Licensing comes with a bash script to create demo data. I recommend doing this on a clean WordPress install.
Before running the import script, be sure to add at least one zip file to your media library. This is used
to create demo products and releases.

From the main plugin directory run the following commands:

```
cd wp-cli
./demo-data.sh <size>
```

Size can be one of `small`, `medium`, `large`, or `giant`. I'd recommend sticking with `small` or `medium`, as the
default Exchange admin won't scale well to a large purchase count.

Running the demo data script will create customers, product, releases, activations and renewal records.

## Recommended Add-ons

1. [Recurring Payments](https://ithemes.com/purchase/recurring-payments-add-on/). The Recurring Payments add-on allows you
 to limit the length of a license. It also allows you configure the license to auto-renew.

2. [Variants](https://ithemes.com/purchase/ithemes-exchange-product-variants-add-on/). The Product Variants add-on allows
you to offer different activation limits depending on the type of license purchased. For example: individual, developer
and unlimited license types.

3. [Stripe](https://ithemes.com/purchase/stripe-add-on/). The Stripe add-on is the easiest way to accept payments for 
your products. However, all payment gateways should work.

4. [wpMandrill](https://wordpress.org/plugins/wpmandrill/). Licensing sends renewal reminders to your customers when their
license key is about to expire. Additionally, emails can be sent to customers who have not yet purchased to the latest
version of your products. By installing the wpMandrill plugin, these notifications will be automatically sent out using
Mandrill's HTTP API instead of `wp_mail()`. This should significantly improve the speed notifications are sent at.

5. [Manual Purchases](https://ithemes.com/purchase/manual-purchases-add-on/). The Manual Purchases add-on allows you
to manually create license keys if needed.

## Help

Formal documentation will be available at launch, but every admin page has a _Help_ tab with inline help for that screen.

## HTTP API

Licensing comes with an HTTP API for handling product updates, site activations and deactivations, etc... The API is
authenticated using Basic Auth for providing license keys and activation records.

A sample [Plugin Updater](https://github.com/iron-bound-designs/itelic-plugin-updater) and 
[Theme Updater](https://github.com/iron-bound-designs/itelic-theme-updater) are provided. You can check these out
to see how the API can be consumed.

An issue describing the initial API can be seen [here](https://github.com/iron-bound-designs/exchange-addon-licensing/issues/8),
but things have changed since the original specification.

## WP CLI

Licensing has robust support for WP CLI. Just about anything you can do from the admin UI can be done via the command line.
All commands are under the main `itelic` command followed by a grouping command. 

For example this following will list the first 20 license keys:

```
wp itelic key list
```

This will disable the license key `abcd-1234`

```
wp itelic key disable abcd-1234
```

You can create a new release from the command line like so. This will create a new _major_ release for the product with 
an id of _7_. The new version will be _1.2_ and the file ID used for updates is _7_. This can be used in combination with the
`wp media import` command to streamline how you release new updates for your software.

```
wp itelic release create --product=5 --version=1.2 --file=7 --type=major
```

## Screenshots

### Licenses

![Edit License](/screenshots/edit-license.png?raw=true "Edit License")

### Releases

![New Release](/screenshots/new-release.png?raw=true "New Release")

![Release Overview](/screenshots/release-overview.png?raw=true "Release Overview")

![Release Stats](/screenshots/release-stats.png?raw=true "Release Stats")

![Release Notify](/screenshots/release-notify.png?raw=true "Release Notify")

### Reports

![Renewal Report](/screenshots/report-renewals.png?raw=true "Renewal Report")

![Licenses Report](/screenshots/report-licenses.png?raw=true "Licenses Report")

![Versions Report](/screenshots/report-versions.png?raw=true "Versions Report")

### Product

![Edit Product](/screenshots/product.png?raw=true "Edit Product")