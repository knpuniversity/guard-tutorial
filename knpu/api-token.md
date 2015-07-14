# How to Authenticate via an API Token

Suppose you want to have an API where users authenticate by sending an `X-TOKEN`
header. With Guard, this is one of the *easiest* things you can setup.

For this example, we have a `User` entity that as an `$apiToken` property that's
auto-generated for every user when they register:

[[[ code('b59c9fbd98') ]]]

But your setup can look however you want: an independent `ApiToken` entity that relates
to your `User`, no `User` entity at all, or api tokens that are validated in some
other way entirely.

## Installing Guard

Read the short [Installation](install) chapter to make sure you've got the
bundle installed and enabled.

## Creating an Authenticator

In Guard, the whole authentication process - reading the `X-TOKEN` header value,
validating it, returning an error response, etc - is handled in a single class called
an "Authenticator". Your authenticator can be as crazy as you want, as long as it
implements [KnpU\Guard\GuardAuthenticatorInterface](https://github.com/knpuniversity/KnpUGuard/blob/master/src/GuardAuthenticatorInterface.php).

Most of the time, you can extend a convenience class called `AbstractGuardAuthenticator`.
Create a new `ApiTokenAuthenticator` class, make it extend this class, and add all
the necessary methods:

[[[ code('30cdaf1984') ]]]

Your mission: fill in each method. We'll get to that in a second.

But to fill on those methods, we'll need to query the database. Let's pass the Doctrine
`EntityManager` into our authenticator:

[[[ code('928d547573') ]]]

## Registering your Authenticator

Before filling in the methods, let's tell Symfony about our fancy new authenticator.
First, register it as a service:

[[[ code('4ea155b7ce') ]]]

Next, update your `security.yml` file to use the new service:

[[[ code('4bf4efdda0') ]]]

Your firewall (called `main` here) can look however you want, as long as it has a
`knpu_guard` section under it with an `authenticators` key that includes the service
name that you setup a second ago (`app.api_token_authenticator` in my example).

***TIP
The other authenticator - `app.form_login_authenticator` - is for my login form.
If you don't need to *also* allow users to login via a form, then you can remove
this. The `entry_point` option is only needed if you have multiple authenticators.
See [How can I use Multiple Authenticators?](multiple-authenticators).
***

<a name="security-providers"></a>

## Filling in the Authenticator Methods

Your authenticator is now being used by Symfony. So let's fill in each method:

### getCredentials()

[[[ code('53dcfe88ec') ]]]

The `getCredentials()` method is called on **every single request** and its job is
to fetch the API token and return it.

Well, that's pretty simple. From here, there are 3 possibilities:

&#35; | Conditions                                  | Result                     | Next Step
----- | ------------------------------------------- | -------------------------- | ----------
A)    | Return non-null value                       | Authentication continues   | [getUser()](#getUser)
B)    | Return null + endpoint requires auth        | Auth skipped, 401 response | [start()](#start)
C)    | Return null+ endpoint does not require auth | Auth skipped, user is anon | nothing

**A)** The `X-TOKEN` header exists, so this returns a non-null value. In this case,
[getUser()](#getUser) is called next.

**B)** The `X-TOKEN` header is missing, so this returns `null`. But, your application
*does* require authentication (e.g. via `access_control` or an `isGranted()` call).
In this case, see [start()](#start).

**C)** The `X-TOKEN` header is missing, so this returns `null`. But the user is accessing
an endpoint that does *not* require authentication. In this case, the request continues
anonymously - no other methods are called on the authenticator.

<a name="getUser"></a>

### getUser()

If `getCredentials()` returns a non-null value, then `getUser()` is called next.
Its job is simple: return a the user (an object implementing `UserInterface`):

[[[ code('47f86a1c25') ]]]

The `$credentials` argument is whatever you returned from `getCredentials()`, and
the `$userProvider` is whatever you've configured in security.yml under the
[providers](#security-providers) key.

You can choose to use your provider, or you can do something else entirely to load
the user. In this case, we're doing a simple query on the `User` entity to see which
User (if any) has this `apiToken` value.

From here, there are 2 possibilities:

&#35; | Conditions                                     | Result                    | Next Step
----- | ---------------------------------------------- | ------------------------- | ----------
A)    | Return a User                                  | Authentication continues  | [checkCredentials()](#checkCredentials)
B)    | Return null or throw AuthenticationException   | Authentication fails      | [onAuthenticationFailure()](#onAuthenticationFailure)

A) If you successfully return a `User` object, then, [checkCredentials()](#checkCredentials)
is called next.

B) If you return `null` or throw any `Symfony\Component\Security\Core\Exception\AuthenticationException`,
authentication will fail and [onAuthenticationFailure()](#onAuthenticationFailure)
is called next.

<a name="checkCredentials"></a>

### checkCredentials()

If you return a user from `getUser()`, then `checkCredentials()` is called next.
Here, you can do any additional checks for the validity of the token - or anything
else you can think of. In this example, we're doing nothing:

[[[ code('9f38945da4') ]]]

Like before, `$credentials` is whatever you returned from `getCredentials()`. And
now, the `$user` argument is what you just returned from `getUser()`.

From here, there are 2 possibilities:

&#35; | Conditions                                                 | Result                    | Next Step
----- | ---------------------------------------------------------- | ------------------------- | -------------
A)    | do anything *except* throwing an `AuthenticationException` | Authentication successful | [onAuthenticationSuccess()](#onAuthenticationSuccess)
B)    | Throw any type of `AuthenticationException`                | Authentication fails      | [onAuthenticationFailure()](#onAuthenticationFailure)

A) If you *don't* throw an exception, congrats! You're authenticated! In this case,
[onAuthenticationSuccess()](#onAuthenticationSuccess) is called next.

B) If you perform extra checks and throw any `Symfony\Component\Security\Core\Exception\AuthenticationException`,
authentication will fail and [onAuthenticationFailure()](#onAuthenticationFailure)
is called next.

<a name="onAuthenticationSuccess"></a>

### onAuthenticationSuccess

Your user is authenticated! Amazing! At this point, in an API, you usually want to
simply let the request continue like normal:

[[[ code('1f5d61727e') ]]]

If you return `null` from this method: the request continues to process through Symfony
like normal (only now, the request is authenticated).

Alternatively, you could return a `Response` object here. If you did, that `Response`
would be returned to the client directly, without executing the controller for this
request. For an API, that's probably not what you want.

<a name="onAuthenticationFailure"></a>

### onAuthenticationFailure

If you return `null` from `getUser()` or throw any `AuthenticationException` from
`getUser()` or `checkCredentials()`, then you'll end up here. Your job is to create
a `Response` that should be sent back to the user to tell them what went wrong:

[[[ code('e8b49612cd') ]]]

In this case, we'll return a 403 Forbidden JSON response with a message about what
went wrong. The `$exception` argument is the actual `AuthenticationException` that
was thrown. It has a `getMessageKey()` method that contains a safe message about
the authentication problem.

This Response will be sent back to the client - the controller will never be executed
for this request.

<a name="start"></a>

### start()

This method is called if an anomymous user accesses en endpoint that requires authentication.
For our example, this would happen if the `X-TOKEN` header is empty (and so `getCredentials()`)
returns `null`. Our job here is to return a Response that instructs the user that
they need to re-send the request with authentication information (i.e. the `X-TOKEN`
header):

[[[ code('f1899c871e') ]]]

In this case, we'll return a 401 Unauthorized JSON response.

### supportsRememberMe

This method is required for all authenticators. If `true`, then the authenticator
will work together with the `remember_me` functionality on your firewall, if you
have it configured. Obviously, for an API, we don't need remember me functionality:

[[[ code('ead9d11584') ]]]

## Testing your Endpoint

Yes! API token authentication is all setup. Let's test it with a simple script.

First, install [Guzzle](http://guzzle.readthedocs.org/en/latest/overview.html#installation):

```bash
composer require guzzlehttp/guzzle:~6.0
```

Next, create a little "play" file at the root of your project that will make a request
to our app. This assume your web server is running on `localhost:8000`:

[[[ code('6ddb4b8b1b') ]]]

In our app, the `anna_admin` user in the database has an `apiToken` of `ABCD1234`.
In other words, this *should* work. Try it out from the command line:

```bash
php testAuth.php
```

If you see a 200 status code with response of "It works!"... well, um... it works!
The `/secure` controller requires authentication. Now try changing the token value
(or removing it entirely) to see our error responses.
