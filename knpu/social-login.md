# Social Login with Facebook

Everybody wants their site to have a "Login with Facebook", "Login with GitHub" or
"Login with InstaFaceTweet". Let's give the people what they want!

Setting this up will take some coding, but the result will be easy to understand
and simple to extend. Let's do it!

***TIP
Watch our [OAuth2 in 8 Steps](http://knpuniversity.com/screencast/oauth) tutorial
first to get handle on how OAuth works.
***

## The Flow

Social authentication uses OAuth - usually the
[authorization code grant type](http://knpuniversity.com/screencast/oauth/authorization-code).
That just means that we have a flow that looks like this:

TODO - IMAGE HERE

1. Your user clicks on a link to "Login with Facebook". This takes them to
   a Symfony controller on your site (e.g. `/connect/facebook`)

2. That controller redirects to Facebook, where they grant your application access

3. Facebook redirects back to your site (e.g. `/connect/facebook-check`) with
   a `?code=` query parameter

4. We make an API request back to Facebook to exchange this code for an access token.
   Then, immediately, we use this access token to make another API request to Facebook
   to fetch the user's information - like their email address. If we find an existing
   user, we can log the user in. If not, we might choose to create a User in the
   database, or have the user complete a "registration" form.

## Installing Guard

Read the short [Installation](install) chapter to make sure you've got the
bundle installed and enabled.

## Creating your Facebook Application

To follow along, you'll need to create a Facebook Application at https://developers.facebook.com/.
You can name it anything, but for the "Site URL", make sure it uses whatever domain
you're using. In my case, I'm using `http://localhost:8000/`. I'll probably create
a different application for my production site.

When you're done, this will give you "App ID" and "App Secret". Keep those handy!

## Installing the OAuth Client Libraries

To help with the OAuth heavy-lifting, we'll use a nice [oauth2-client](TOODOOOOOOOO)
library, and its [oauth2-facebook](https://github.com/thephpleague/oauth2-facebook)
helper library. Get these installed:

```bash
composer require league/oauth2-client:~1.0@dev league/oauth2-facebook
```

## Setting up the Facebook Provider Service

The oauth2-facebook library lets us create a nice `Facebook` object that makes doing
OAuth with Facebook a breeze. We'll need this object in several places, so let's register
it as a service:

[[[ code('2299b2f677') ]]]

This references two new parameters - `facebook_app_id` and `facebook_app_secret`.
Add these to your `parameters.yml` (and `parameters.yml.dist`) file with your Facebook
application's "App ID" and "App Secret" values:

[[[ code('01b69ed14c') ]]]

***TIP
If you haven't seen the odd `"@=service('router')..."` expression syntax before,
we have a blog post on it:
[Symfony Service Expressions: Do things you thought Impossible](http://knpuniversity.com/blog/service-expressions).
***

## Creating the FacebookConnectController

Don't think about security yet! Instead, look at the flow above. We'll need a controller
with *two* actions: one that simply redirects to Facebook for authorization (`/connect/facebook`)
and another that handles what happens when Facebook redirects back to us (`/connect/facebook-check`):

[[[ code('dcd753998b') ]]]

The first URL - `/connect/facebook` - uses the Facebook provider service from the
oauth2-client library that we just setup. Its job is simple: redirect to Facebook
to start the authorization process. In a second, we'll add a "Login with Facebook"
link that will point here.

The second URL - `/connect/facebook-check` - will be the URL that Facebook will
redirect back to after. But notice it doesn't do anything - and it never will. Another
layer (the authenticator) will intercept things and handle all the logic.

For good measure, let's create a "Login with Facebook" on our normal login page:

[[[ code('797ea77de9') ]]]

## Creating an Authenticator

With Guard, the whole authentication process - fetching the access token, getting
the user information, redirecting after success, etc - is handled in a
single class called an "Authenticator". Your authenticator can be as crazy as you
want, as long as it implements [KnpU\Guard\GuardAuthenticatorInterface](https://github.com/knpuniversity/KnpUGuard/blob/master/src/GuardAuthenticatorInterface.php).

Most of the time, you can extend a convenience class called `AbstractGuardAuthenticator`.
Create a new `FacebookAuthenticator` class, make it extend this class, and add all
the necessary methods:

[[[ code('8fd00c3f3c') ]]]

Your mission: fill in each method. We'll get to that in a second.

To fill in those methods, we're going to need some services. To keep this tutorial
simple, let's pass the entire container into our authenticator:

[[[ code('46b27bcaab') ]]]

***TIP
For seasoned-Symfony devs, you can of course inject *only* the services you need.
***

## Registering your Authenticator

Before filling in the methods, let's tell Symfony about our fancy new authenticator.
First, register it as a service:

[[[ code('478a1211c0') ]]]

Next, update your `security.yml` file to use the new service:

[[[ code('7372399109') ]]]

Your firewall (called `main` here) can look however you want, as long as it has a
`knpu_guard` section under it with an `authenticators` key that includes the service
name that you setup a second ago (`app.facebook_authenticator` in my example).

## Filling in the Authenticator Methods

Congratulations! Your authenticator is now being used by Symfony. Here's the flow
so far:

1) The user clicks "Login with Facebook";
2) Our `connectFacebookAction` redirects the user to Facebook;
3) After authorizing our app, Facebook redirects back to `/connect/facebook-connect`;
4) The `getCredentials()` method on `FacebookAuthenticator` is called and we start
   working our magic!

<a name="getCredentials"></a>

### getCredentials()

[[[ code('4cfa91bec3') ]]]

If the user approves our application, Facebook will redirect back to `/connect/facebook-connect`
with a `?code=ABC123` query parameter. That's called the "authorization code".

The `getCredentials()` method is called on **every single request** and its job is
simple: grab this "authorization code" and return it.

Inside `getCredentials()`, here are 2 possible paths:

&#35;  | Conditions            | Result                    | Next Step
------ | --------------------- | ------------------------- | ----------
A)     | Return non-null value | Authentication continues  | [getUser()](#getUser)
B)     | Throw an exception    | Authentication fails      | [onAuthenticationFailure()](#onAuthenticationFailure)
C)     | Return null           | Authentication is skipped | Nothing!


**A)** If the URL is `/connect/facebook-connect`, then we fetch the `code` query
parameter that Facebook is sending us and return it. This will be passed to a few
other methods later. In this case - since we returned a non-null value from `getCredentials()` -
the [getUser()](#getUser) method is called next.

**B)** If the URL is `/connect/facebook-connect` but there is no `code` query parameter,
something went wrong! This probably means the user didn't authorize our app. To
fail authentication, you can throw any `AuthenticationException`. The
[CustomAuthenticationException](error-messages) is just a cool way to control the
message the user sees.

**C)** If the URL is *not* `/connect/facebook-connect`, we return `null`. In this
case, the request continues anonymously - no other methods are called on the authenticator.

<a name="getUser"></a>

### getUser()

If `getCredentials()` returns a non-null value, then `getUser()` is called next.
Its job is simple: return a user (an object implementing [UserInterface](http://symfony.com/doc/current/cookbook/security/entity_provider.html#what-s-this-userinterface)).

But to do that, there are several steps. Ultimately, there are two possible results:

&#35; | Conditions                                        | Result                    | Next Step
----- | ------------------------------------------------- | ------------------------- | -------------
A)    | Return a User object                              | Authentication continues  | [checkCredentials()](#checkCredentials)
B)    | Return null or throw an `AuthenticationException` | Authentication fails      | Redirect to  [getLoginUrl()](#getLoginUrl)

#### getUser() Part 1: Get the access token

The `$authorizationCode` argument is whatever you returned from `getCredentials()`.
Our first job is to talk to the Facebook API and exchange this for an "access token".
Fortunately, with the oauth2-client library, this is easy:

[[[ code('03af090698') ]]]

If this fails for some reason, we throw an AuthenticationException (specifically
a `CustomAuthenticationException` to control the message).

#### getUser() Part 2: Get Facebook User Information

Now that we have a valid access token, we can make reqeusts to the Facebook API
on behalf of the user. The most important thing we need is information about the
user - like what is their email address?

To get that, use the `getResourceOwner()` method:

[[[ code('7bfe403f0d') ]]]

This returns a `FacebookUser` from the `oauth2-facebook` library (if you connect to
something else like GitHub, it will have a different object). We can use that to
get the user's email address.

#### getUser() Part 3: Fetching/Creating the User

Great! We now know some information about the user, including their email address.




**A)** If you return some `User` object (using whatever method you want) - then
you'll continue on to [checkCredentials()](#checkCredentials).

**B**  If you return `null` or throw any `Symfony\Component\Security\Core\Exception\AuthenticationException`,
authentication will fail and the user will be redirected back to the login page:
see [getLoginUrl()](#getLoginUrl).

<a name="checkCredentials"></a>

### checkCredentials()

If you return a user from `getUser()`, then `checkCredentials()` is called next.
Your job is simple: check if the username/password combination is valid. If it isn't,
throw a `BadCredentialsException` (or any `AuthenticationException`):

[[[ code('95ef4c0238') ]]]

Like before, `$credentials` is whatever you returned from `getCredentials()`. And
now, the `$user` argument is what you just returned from `getUser()`. To check the
user, you can use the `security.password_encoder`, which automatically hashes the
plain password based on your `security.yml` configuration.

Want to do some other custom checks beyond the password? Go crazy! Based on what
you do, there are 2 paths:

&#35; | Conditions                                                 | Result                    | Next Step
----- | ---------------------------------------------------------- | ------------------------- | -------------
A)    | do anything *except* throwing an `AuthenticationException` | Authentication successful | Redirect the user (may involve [getDefaultSuccessRedirectUrl()](#getDefaultSuccessRedirectUrl))
B)    | Throw any type of `AuthenticationException`                | Authentication fails      | Redirect to [getLoginUrl()](#getLoginUrl)

If you *don't* throw an exception, congratulations! You're user is now authenticated,
and will be redirected somewhere...

<a name="getDefaultSuccessRedirectUrl"></a>

### getDefaultSuccessRedirectUrl()

Your user is now authenticated. Woot! But, where should we redirect them? The `AbstractFormLoginAuthenticator`
class takes care of *most* of this automatically. If the user originally tried to
access a protected page (e.g. `/admin`) but was redirected to the login page, then
they'll now be redirected back to that URL (so, `/admin`).

But what if the user went to `/login` directly? In that case, you'll need to decide
where they should go. How about the homepage?

[[[ code('845a83722f') ]]]

This fetches the `router` service and redirects to a `homepage` route (change this
to a real route in your application). But note: this method is *only* called if there
isn't some previous page that user should be redirected to.

<a name="getLoginUrl"></a>

### getLoginUrl()

If authentication fails in `getUser()` or `checkCredentials()`, the user will be
redirected back to the login page. In this method, you just need to tell Symfony
where your login page lives:

[[[ code('362a033205') ]]]

In our case, the login page route name is `security_login`.


















## Installing Guard

Read the short [Installation](install) chapter to make sure you've got the
bundle installed and enabled.

## Creating an Authenticator

With Guard, the whole authentication process - fetching the username/password POST
values, validating the password, redirecting after success, etc - is handled in a
single class called an "Authenticator". Your authenticator can be as crazy as you
want, as long as it implements [KnpU\Guard\GuardAuthenticatorInterface](https://github.com/knpuniversity/KnpUGuard/blob/master/src/GuardAuthenticatorInterface.php).

For login forms, life is easier, thanks to a convenience class called `AbstractFormLoginAuthenticator`.
Create a new `FormLoginAuthenticator` class, make it extend this class, and add all
the missing methods (from the interface and abstract class):

[[[ code('1334f477a3') ]]]

Your mission: fill in each method. We'll get to that in a second.

To fill in those methods, we're going to need some services. To keep this tutorial
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

## Filling in the Authenticator Methods

Your authenticator is now being used by Symfony. So let's fill in each method:

<a name="getCredentials"></a>

### getCredentials()

[[[ code('2bc735ebfe') ]]]

The `getCredentials()` method is called on **every single request** and its job is
either to fetch the username/password from the request and return them.

So, from here, there are 2 possibilities:

&#35;  | Conditions            | Result                    | Next Step
------ | --------------------- | ------------------------- | ----------
A)     | Return non-null value | Authentication continues  | [getUser()](#getUser)
B)     | Return null           | Authentication is skipped | Nothing! But if the user is anonymous and tries to access a secure page, [getLoginUrl()](#getLoginUrl) will be called

**A)** If the URL is `/login_check` (that's the URL that our login form submits to),
then we fetch the `_username` and `_password` post parameters (these were our
[form field names](#create-login-form-template)) and return them. Whatever you return
here will be passed to a few other methods later. In this case - since we returned
a non-null value from `getCredentials()` - the [getUser()](#getUser) method is called
next.

**B)** If the URL is *not* `/login_check`, we return `null`. In this case, the request
continues anonymously - no other methods are called on the authenticator. If the
page the user is accessing requires login, they'll be redirected to the login form:
see [getLoginUrl()](#getLoginUrl).

***TIP
We also set a `Security::LAST_USERNAME` key into the session. This is optional, but
it lets you pre-fill the login form with this value (see the
[SecurityController::loginAction](#SecurityController-loginAction) from earlier).
***

<a name="getUser"></a>

### getUser()

If `getCredentials()` returns a non-null value, then `getUser()` is called next.
Its job is simple: return a user (an object implementing [UserInterface](http://symfony.com/doc/current/cookbook/security/entity_provider.html#what-s-this-userinterface)):

[[[ code('ce30843cf7') ]]]

The `$credentials` argument is whatever you returned from `getCredentials()` and
the `$userProvider` is whatever you've configured in security.yml under the
[providers](#security-providers) key. My provider queries the database and returns
the `User` entity.

There are 2 paths from there:

&#35; | Conditions                                        | Result                    | Next Step
----- | ------------------------------------------------- | ------------------------- | -------------
A)    | Return a User object                              | Authentication continues  | [checkCredentials()](#checkCredentials)
B)    | Return null or throw an `AuthenticationException` | Authentication fails      | Redirect to  [getLoginUrl()](#getLoginUrl)

**A)** If you return some `User` object (using whatever method you want) - then
you'll continue on to [checkCredentials()](#checkCredentials).

**B**  If you return `null` or throw any `Symfony\Component\Security\Core\Exception\AuthenticationException`,
authentication will fail and the user will be redirected back to the login page:
see [getLoginUrl()](#getLoginUrl).

<a name="checkCredentials"></a>

### checkCredentials()

If you return a user from `getUser()`, then `checkCredentials()` is called next.
Your job is simple: check if the username/password combination is valid. If it isn't,
throw a `BadCredentialsException` (or any `AuthenticationException`):

[[[ code('95ef4c0238') ]]]

Like before, `$credentials` is whatever you returned from `getCredentials()`. And
now, the `$user` argument is what you just returned from `getUser()`. To check the
user, you can use the `security.password_encoder`, which automatically hashes the
plain password based on your `security.yml` configuration.

Want to do some other custom checks beyond the password? Go crazy! Based on what
you do, there are 2 paths:

&#35; | Conditions                                                 | Result                    | Next Step
----- | ---------------------------------------------------------- | ------------------------- | -------------
A)    | do anything *except* throwing an `AuthenticationException` | Authentication successful | Redirect the user (may involve [getDefaultSuccessRedirectUrl()](#getDefaultSuccessRedirectUrl))
B)    | Throw any type of `AuthenticationException`                | Authentication fails      | Redirect to [getLoginUrl()](#getLoginUrl)

If you *don't* throw an exception, congratulations! You're user is now authenticated,
and will be redirected somewhere...

<a name="getDefaultSuccessRedirectUrl"></a>

### getDefaultSuccessRedirectUrl()

Your user is now authenticated. Woot! But, where should we redirect them? The `AbstractFormLoginAuthenticator`
class takes care of *most* of this automatically. If the user originally tried to
access a protected page (e.g. `/admin`) but was redirected to the login page, then
they'll now be redirected back to that URL (so, `/admin`).

But what if the user went to `/login` directly? In that case, you'll need to decide
where they should go. How about the homepage?

[[[ code('845a83722f') ]]]

This fetches the `router` service and redirects to a `homepage` route (change this
to a real route in your application). But note: this method is *only* called if there
isn't some previous page that user should be redirected to.

<a name="getLoginUrl"></a>

### getLoginUrl()

If authentication fails in `getUser()` or `checkCredentials()`, the user will be
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

