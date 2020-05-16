# BurstPayment

[![CI](https://github.com/felixbrucker/shopware-burst-payment/workflows/CI/badge.svg)](https://github.com/felixbrucker/shopware-burst-payment/actions?query=workflow:CI)
[![Software License](https://img.shields.io/badge/license-GPL--3.0-brightgreen.svg)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/felixbrucker/shopware-burst-payment)](https://packagist.org/packages/felixbrucker/shopware-burst-payment)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/felixbrucker/shopware-burst-payment)](https://packagist.org/packages/felixbrucker/shopware-burst-payment)

Burst payment integration for Shopware 6

## Requirements

| Version 	| Requirements               	|
|---------	|----------------------------	|
| 1.0.0    	| Min. Shopware 6.2, PHP 7.2+ 	|

## Installation

This plugin can be installed via composer or as a .zip file via the administration.

### Composer

```bash
composer require felixbrucker/shopware-burst-payment
bin/console plugin:install --activate BurstPayment
cd vendor/felixbrucker/shopware-burst-payment && npm install
./psh.phar administration:build
bin/console assets:install
```

### .zip File

Obtain the latest release zip from [here](https://github.com/felixbrucker/shopware-burst-payment/releases/latest) and upload it in your shopware administration.
