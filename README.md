

VR Payment Plugin for Gambio 4
=============================

The VR Payment plugin wraps around the VR Payment API. This library facilitates your interaction with various services such as transactions.

## Requirements

- PHP 7.2 to PHP 8.2
- Gambio 4.5 to Gambio 4.9

We only support the Gambio standard checkout (without modifications)

## Installation

**Please install it manually**

### Manual Installation


1. Alternatively you can download the package in its entirety. The [Releases](../../releases) page lists all stable versions.

2. Uncompress the zip file you download

3. Include it to your Gambio shop root folder

4. Run the install command
```bash
# Please go to /GXModules/VRPayment/VRPaymentPayment and run the command
composer install
```

5. Login to Admin Panel

6. Click on Toolbox > Clear Cache and clear all caches

7. Click on Modules > Module Center > VR Payment Payment

8. Install the module and clear the cache again (repeat step 5)

9. Select Modules > Module Center > VR Payment Payment again and click Edit

10. Enter correct data from VR Payment API and click Save. Payment methods will be synchronised

11. Navigate To Modules -> Payment Systems

12. Click on "Miscellaneous" tab and find "added modules" and click the VR Payment Payment.

13. Install the VR Payment Payment System

14. Click Edit, select payment methods that you want to use and save configuration (Payment methods are synchronized from VR Payment and only if they are enabled)

## Usage
The library needs to be configured with your account's space id, user id, and application key which are available in your VR Payment
account dashboard.

## Documentation

[Documentation](https://gateway.vr-payment.de/doc/gambio-4/1.0.25/docs/en/documentation.html)

## License

Please see the [license file](https://github.com/vr-payment/gambio-4/blob/master/LICENSE.txt) for more information.
