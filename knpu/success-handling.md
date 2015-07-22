# Customizing Success Handling

So, your authentication is working. Yes! Now, what if you need to hook into what
happens next? For example, maybe you need to redirect to a special page, or return
JSON instead of a redirect in some cases. Or perhaps you want to store the last login
time of your user. All that is possible and easy.

## onAuthenticationSuccess()

Every authenticator has a `onAuthenticationSuccess()` method. This is called whenever
authentication is completed, and it has one job: create a `Response` that should
be sent back to the user. This could be a redirect back to the last page the user
visited or return `null` and let the request continue (see [API token](api-token)).

If you extend certain authenticators - like `AbstractFormLoginAuthenticator` - then
this method is filled in for you automatically. But you can feel free to override it
and customize.

## Sending back JSON for AJAX

Suppose your [login form](login-form) uses AJAX. Instead of redirecting after success,
you probably want it to return some sort of JSON response. Just override `onAuthenticationSuccess()`:

[[[ code('0041f970c4') ]]]

That's it! If you login via AJAX, you'll receive a JSON response instead of the
redirect.

## Performing an Action on Login

Suppose you want to store the "last login" time for the user in the database. You
*could* override `onAuthenticationSuccess()`, update the User and save.

But, there's a better way: Symfony security system dispatches a `security.interactive_login`
event that you can hook into. Why is this better? Because this will be called whenever
a user logs in, whether it is via this authenticator, another authenticator or some
non-Guard system.

First, make sure you have a column on your user:

[[[ code('65a4b3aa56') ]]]

Next, create an event subscriber. This will be called whenever a user logs in. It's
job is simple: update this `lastLoginTime` property and save the User:

[[[ code('273fcd52b5') ]]]

***TIP
Not familiar with listeners or susbcribers? Check out
[Interrupt Symfony with an Event Subscriber](http://knpuniversity.com/screencast/symfony-journey/event-subscriber)
***

Now, just register this as a service and tag it so that Symfony know about the subscriber:

[[[ code('31b10f28a4') ]]]

That's all you need. Next time you login, the User's `lastLoginTime` will automatically
be updated in the database.
