# How to Login with a username *or* email (or crazier)   

Whenever you login, you identify yourself. For a form, this might be with a username
or email. With an [API](api-token), the token often serves both to identify *who*
you are and serve as a sort of "password".

With Guard, you can use any crazy combination of methods to figure out *who* is
trying to authenticate. The only rule is that your [getUser](login-form#getUser)
function returns *some* object that implements [UserInterface](http://symfony.com/doc/current/cookbook/security/entity_provider.html#what-s-this-userinterface).

Let's look at an example of *how* you could customize this:

## Logging in with a username *or* email

In the [Form Login](login-form) chapter, we built a login form that queries for
a user from the database using the `username` property:

[[[ code('ce30843cf7') ]]]

But what if we wanted to let the user enter his username *or* email? First, create
a method inside your `UserRepository` for this query:

[[[ code('7a3076c940') ]]]

Now, in `getUser()`, simply call this method to return your `User` object:

[[[ code('63ad655faf') ]]]

This works because we're injecting the entire service container. But, you could just
as easily inject *only* the entity manager to clean things up.

Now, wasn't that easy? Have some other weird requirement for how a user is loaded?
Do whatever you want inside of `getUser()`.

***TIP
Why not use the `$userProvider` argument? The `$userProvider` that's passed to us
here is what we have configured in `security.yml` under the [providers](login-form#security-providers)
key. In this project, this object gives us a `loadUserByUsername` method that queries
for the `User` by the username. We *could* customize the user provider and make
it do what we want. Or, we could simply fetch our repository directly and query
for what we need. That seems much easier, and I've yet to see a downside.
***
