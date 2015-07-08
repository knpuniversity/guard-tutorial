# Installation

Installation is easy! So let's get it behind us!

## 1) Install the Library via Composer

[Download Composer](https://getcomposer.org/download/) and then run this command
from inside your project.

```bash
composer require knpuniversity/guard-bundle:~0.1@dev
```

Use `php composer.phar require knpuniversity/guard-bundle:~0.1@dev` if you don't
have Composer installed globally.

## 2) Enable the Bundle

Find your `app/AppKernel.php` file and enable the bundle:

[[[ code('5fe4c20ab2') ]]]

## 3) Build your first authenticator!

You're ready to build your authentication system!

**A)** Learn the fundamentals by [building a login form](login-form)

**B)** Create an [API token authentication system](api-token)
