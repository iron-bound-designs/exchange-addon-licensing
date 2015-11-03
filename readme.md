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

cd wp-cli
```
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

## Help

Formal documentation will be available at launch, but every admin page has a _Help_ tab with inline help for that screen.