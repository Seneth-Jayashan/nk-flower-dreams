# NK Flower Dreams Website

A PHP + MySQL website for NK Flower Dreams with:
- A public storefront homepage (`index.php`) that shows active products

## Tech Stack

- PHP 8.1+
- MySQL 8+ (or compatible MariaDB)
- Apache (recommended via XAMPP)
- Plain HTML/CSS/JS frontend (no build step)

## Project Structure

```
NK Flower Dreams/
  index.php                 # Public homepage (dynamic product data)
  style.css                 # Public styles
  script.js                 # Public interactions/animations
  images/
    collections/            # Default storefront images
    uploads/                # Product images used on the public site
```

## What Changed

- The static `index.html` has been removed because the real entry point is `index.php`, which includes dynamic product loading from the database.

## Features

### Public Website
- SEO meta tags + social tags (OpenGraph, Twitter)
- Animated landing page and sectioned storefront UI
- Dynamic product carousel from the `products` table
- Displays only products where `is_active = 1`

## Prerequisites

1. XAMPP (or equivalent Apache + PHP + MySQL stack)
2. PHP 8.1 or newer
3. MySQL/MariaDB running

## Local Setup (XAMPP)

1. Place project folder in `C:/xampp/htdocs/`.
2. Start Apache and MySQL from XAMPP Control Panel.
3. Open the website in your browser.

## Run the App

- Public site:
  - `http://localhost/NK%20Flower%20Dreams/index.php`

## Notes

- Administration and backend management details are intentionally excluded from this README.
- This document is focused on public website usage only.

## Author

- S JAY | One X Universe (pvt) Ltd
- All Rights Reserved ©

