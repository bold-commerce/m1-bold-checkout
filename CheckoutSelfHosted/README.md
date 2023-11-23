# M1 Self-Hosted Bold Checkout with React App.

## Installation

1. Copy contents of the repository to `app/code/community/Bold/CheckoutSelfHosted/`
2. `cp app/code/community/Bold/CheckoutSelfHosted/etc/Bold_CheckoutSelfHosted.xml app/etc/modules/`
3. Run `cp -r app/code/community/Bold/CheckoutSelfHosted/design/* app/design/`
4. Run `cp -r app/code/community/Bold/CheckoutSelfHosted/media/* media/`
5. Clean cache.
6. Navigate to Magento admin area System > Configuration > Sales > Checkout > Bold Checkout Integration Advanced Settings on Website Scope.
7. Select Bold Checkout Type as "Self-Hosted(React Application)".
8. Save configuration.

## Setup React App for Self-Hosted Bold Checkout(For development purpose only)
1. Clone and setup react app templates. https://github.com/bold-commerce/checkout-experience-templates#set-up-the-template.
2. Run React app "yarn serve" from React app root dir.
3. Navigate to Magento admin area System > Configuration > Sales > Checkout > Bold Checkout Integration Advanced Settings on Website Scope.
4. Set "Self Hosted Checkout Experience Templates App Url" to your React App URL. (e.g.  http://localhost:8080/)
5. Save configuration.
