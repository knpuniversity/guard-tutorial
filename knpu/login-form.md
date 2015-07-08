# How to Create a Login Form

------> be more encouraging about how this flow makes a lot of good sense. Think of
    the possibilities!

Creating a login form is simple, and will be *really* flexible. You still need to
do some work, but trust me - you'll be really happy with the result.

***TIP
Click **Download** to get the starting or finished code of this tutorial.
***

<a name="SecurityController-loginAction"></a>

## Create the Login Form

Don't think about security yet! Instead, start by creating two Symfony controllers:
one for rendering the login form and another that'll handle the login submit::

[[[ code('586d873109') ]]]

So far, this is just a lovely, but boring set of controllers. The only interesting
parts are the `last_username` and `error` variables. Where are those coming from?
You'll see. Also, `loginCheckAction` doesn't do anything - and it never will. That'll
make sense soon.

<a name="create-login-form-template"></a>

Next, create the login template:

[[[ code('771878ae65') ]]]

Nothing interesting here either! The field names are `_username` and `_password`,
but these could be anything (see [getCredentials()](#getCredentials)).

## Installing Guard

Read the short [Installation](installation) chapter to make sure you've got the
bundle installed and enabled.

## Creating an Authenticator

In Guard, the whole authentication process - fetching the username/password POST
values, validating the password, redirecting after success, etc - is handled in a
single class called an "Authenticator". Your authenticator can be as crazy as you
want, as long as it implements `KnpU\Guard\GuardAuthenticatorInterface`.

For login forms, life is easier, thanks to a convenience class called `AbstractFormLoginAuthenticator`.
Create a new `FormLoginAuthenticator` class, make it extend this class, and add all
the necessary methods.

[[[ code('1334f477a3') ]]]

Your mission: fill in each method. We'll get to that in a second.

But to fill on those methods, we're going to need some services. To keep this tutorial
simple, let's pass the entire container into our authenticator:

[[[ code('7a83cbc309') ]]]

***TIP
For seasoned-Symfony devs, you can of course inject *only* the services you need.
***

## Registering your Authenticator

Before filling in the methods, let's tell Symfony about our fancy new authenticator.
First, register it as a service:

[[[ code('a83921bba1') ]]]

Next, update your `security.yml` file to use the new service:

[[[ code('b5384b49a0') ]]]

Your firewall (called `main` here) can look however you want, as long as it has a
`knpu_guard` section under it with an `authenticators` key that includes the service
name that you setup a second ago (`app.form_login_authenticator` in my example).

<a name="security-providers"></a>

I've also setup my "user provider" to load my users from the database:

[[[ code('0f206d4cda') ]]]

In a minute, you'll see where that's used.

## Authenticating your User

Your authenticator is now being used by Symfony. So let's fill in each method:

<a name="getCredentials"></a>

### getCredentials()

[[[ code('2bc735ebfe') ]]]

The `getCredentials()` method is called on **every single request** and its job is
either to:

A) Fetch the username and password from the request and return them;
or
B) Return null if you want to skip authentication.

Since our login form submits to `/login_check`, if the URL (`getPathInfo()`)
is *not* equal to this, we should return `null`. Returning `null` skips authentication:
nothing else is called on the authenticator. The user may be logged in thanks to
a cookie from a previous request, but we don't want to do any now.

If the URL *is* `/login_check`, fetch the `_username` and `_password` post parameters
(these were our [form field names](#create-login-form-template)) and return them.
The format/keys of the return value can be anything: these credentials are passed
later to the `getUser()` and `checkCredentials()` methods in the authenticator.

***TIP
We also set a `Security::LAST_USERNAME` key into the session. This is optional, but
it lets you pre-fill the login form with this value (see the
[SecurityController::loginAction](#SecurityController-loginAction) from earlier).
***

### getUser()

If `getCredentials()` returns a non-null value, then `getUser()` is called next.
Its job is simple: return a the user (an object implementing `UserInterface`):

[[[ code('ce30843cf7') ]]]

The `$credentials` argument is whatever you returned from `getCredentials()`, and
the `$userProvider` is whatever you've configured in security.yml under the
[providers](#security-providers) key. My provider queries the database and returns
the `User` entity.

If you return `null` or throw any `Symfony\Component\Security\Core\Exception\AuthenticationException`,
authentication will fail and the user will be redirected back to the login page.

### checkCredentials()

If you return a user from `getUser()`, then `checkCredentials()` is called next.
Your job is simple: check if the username/password combination is valid. If it isn't,
throw a `BadCredentialsException` (or any `AuthenticationException`):

[[[ code('95ef4c0238') ]]]

Like before, `$credentials` is whatever you returned from `getCredentials()`. And
now, the `$user` argument is what you just returned from `getUser()`. To check the
user, you can use the `security.password_encoder`, which automatically hashes the
plain password based on your `security.yml` configuration.

Want to do some other custom checks beyond the password? Go crazy!

If you *don't* throw an exception, congratulations! You're user is now authenticated,
and will be redirected somewhere...

### getDefaultSucessUrl()

Your user is now authenticated. Woot! But, where should we redirect them? The `AbstractFormLoginAuthenticator`
class takes care of *most* of this automatically. If the user originally tried to
access a protected page (e.g. `/admin`) but was redirected to the login page, then
they'll now be sent back to that URL (so, `/admin`).

But what if the user went to `/admin` directly? In that case, you'll need to decide
where they should go. How about the homepage?

[[[ code('845a83722f') ]]]

This fetches the `router` service and redirects to a `homepage` route (change this
to a real route in your application). But note: this method is *only* called if there
isn't some previous page that user should be redirected to.

### getLoginUrl()

If authentication fails in `getUser()` or `checkCredentials()`, the use will be
redirected back to the login page. In this method, you just need to tell Symfony
where your login page lives:

[[[ code('362a033205') ]]]

In our case, the login page route name is `security_login`.

## Customize!

Try it out! You should be able to login, see login errors, and control most of the
process. So what else can we customize?

* [How can I login by username *or* email (or any other weird way)?](login-form-customize-user)
* [How can I customize the error messages?](login-form-error-messages)
* [How can I control/hook into what happens when login fails?](login-form-failure-handling)
* [How can I control/hook into what happens on login success?](login-form-success-handling)
* [How can I add a CSRF token?](login-form-csrf)
