# M1 Self-Hosted Bold Checkout with React App.

## Installation

1. Copy contents of the repository to `app/code/community/Bold/CheckoutSelfHosted/`
2. `cp app/code/community/Bold/CheckoutSelfHosted/etc/Bold_CheckoutSelfHosted.xml app/etc/modules/`
3. Run `cp -r app/code/community/Bold/CheckoutSelfHosted/design/* app/design/`
4. Clean cache.
5. Clone and setup react app templates. https://github.com/bold-commerce/checkout-experience-templates#set-up-the-template.
6. Run React app "yarn serve" from React app root dir.
6. Navigate to Magento admin area System > Configuration > Sales > Checkout > Bold Checkout Integration Advanced Settings.
7. Set "Enable Bold Checkout Self Hosted" to "Yes".
8. Set "Template Path" to your React App URL. (e.g.  http://localhost:8080/)
9. Save configuration.
