# Customizing Failure Handling


Authentication can fail inside your authenticator in any of these 3 functions:

* `getCredentials()`
* `getUser()`
* `checkCredentials()`

The [Customizing Authentication Failure Messages](error-messages) tutorial tells
you *how* you can fail authentication and how to customize the error message when
that happens.

But if you need more control, use the `onAuthenticationFailure()` method.

## onAuthenticationFailure()

Every authenticator has a `onAuthenticationFailure()` method. This is called whenever
authentication fails, and it has one job: create a `Response` that should be sent
back to the user. This could be a redirect back to the login page or a 403 JSON
response.

If you extend certain authenticators - like `AbstractFormLoginAuthenticator` - then
this method is filled in for you automatically. But you can feel free to override
it and customize.

## Sending back JSON for AJAX

Suppose your [login form](login-form) uses AJAX. Instead of redirecting to `/login`
on a failure, you probably want it to return some sort of JSON response. Just
override `onAuthenticationFailure()`:

[[[ code('1d0f823294') ]]]

That's it! If you fail authentication via AJAX, you'll receive a JSON response instead
of the redirect.
