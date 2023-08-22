# m1-bold-checkout

## Installation

1. Copy contents of the repository to `app/code/community/Bold`
2. `cp app/code/community/Bold/Checkout/etc/Bold_Checkout.xml app/etc/modules/`
3. Run `cp -r app/code/community/Bold/Checkout/design/* app/design/`
4. Run `cp -r app/code/community/Bold/Checkout/locale/* app/locale/`
5. In case you have System > Configuration > Sales > Tax > Calculation Settings > Catalog Prices = "Including Tax" you need to enable
    Payment options > Tax settings > Include taxes in the price of my products in the Bold Account Center.
6. Clean cache
