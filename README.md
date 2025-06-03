# My Earn Money Bot

This is a Telegram bot that requires users to join specific channels before accessing a web application.

## Features
- Checks if a user has joined predefined Telegram channels.
- Provides a web app button upon successful verification.

## Deployment
This bot is designed to be deployed on Render (or any PHP-supporting web hosting).

### Setup Steps:
1.  Create a new Telegram bot using @BotFather to get your `BOT_TOKEN`.
2.  Update `YOUR_BOT_TOKEN` and `YOUR_WEB_APP_URL` in `index.php` or set them as environment variables on Render.
3.  Deploy the `index.php` file to a Render Web Service (PHP environment).
4.  Set up the Telegram webhook by visiting:
    `https://your-render-app-name.onrender.com/index.php?setup=1`
    (Replace `your-render-app-name.onrender.com` with your actual Render app URL).

## Channels to Join
- [Channel 1](https://t.me/global_Fun22)
- [Channel 2](https://t.me/instant_earn_airdrop)
- [Channel 3](https://t.me/myearn_Cash_payment)

## Web App
The web app URL is configured in the `index.php` file.
