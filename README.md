CONTENTS OF THIS FILE
----------------------
* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

Enhance your Meta/Facebook marketing efforts with the Meta Conversions API
module for Drupal. This tool helps your website communicate directly with
Meta/Facebook, bypassing common browser restrictions for more effective
advertising. It's designed to improve your ad targeting and give you clearer
insights into how your campaigns are performing, making it easier than ever to
achieve better results.

Supported events by the module:

* PageView (browser event)
* ViewContent (server side event)
* AddToCart (server side event)

Find out more information about the module at:
[https://gorannikolovski.com/product/meta-conversions-api-for-drupal](https://gorannikolovski.com/product/meta-conversions-api-for-drupal).


REQUIREMENTS
------------

The Meta Conversions API requires the installation of both the Drupal Commerce
and State Machine modules.


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module.
  See: https://www.drupal.org/docs/extending-drupal/installing-modules
  for further information.


CONFIGURATION
-------------

You can configure the module at the following page:

```
/admin/config/services/meta-conversions-api
```

Get your Pixel ID and access token data by visiting the Meta/Facebook Events
Manager, then select the appropriate project (Data source) and go to the
Settings tab. Find your Pixel ID, referred to as the Dataset ID. Then, generate
the access token by clicking on the 'Generate access token' link in the
Conversions API section.


MAINTAINERS
-----------

Current maintainers:

* Goran Nikolovski (gnikolovski)
  - https://gorannikolovski.com
  - https://www.drupal.org/u/gnikolovski
