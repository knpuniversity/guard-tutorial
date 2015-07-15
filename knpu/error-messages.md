# Customizing Authentication Failure Messages

Authentication can fail for a lot of reasons: an invalid username, bad password,
locked account, etc, etc. And whether we're building a login form or an API, you
need to give your users the *best* possible error message so they know how to fix
things. If your error message is "Authentication error" when they type in a bad password,
you're doing it wrong.

## How and Where to Fail Authentication

Authentication can fail inside your authenticator in any of these 3 functions:

* `getCredentials()`
* `getUser()`
* `checkCredentials()`

Causing an authentication failure is easy: simply throw *any* instance of
Symfony's `Symfony\Component\Security\Core\Exception\AuthenticationException`. In
fact, if you return `null` from `getUser()`, Guard automatically throws a
[UsernameNotFoundException](https://github.com/symfony/symfony/blob/2.8/src/Symfony/Component/Security/Core/Exception/UsernameNotFoundException.php),
which extends `AuthenticationException`.

## Controlling the Message with CustomAuthenticationException

Any class that extends `AuthenticationException` has a hardcoded message that it
causes. Here are some examples:

Class  | Message            
------ | ---------------------
[UsernameNotFoundException](https://github.com/symfony/symfony/blob/2.8/src/Symfony/Component/Security/Core/Exception/UsernameNotFoundException.php) | `Username could not be found.`
[BadCredentialsException](https://github.com/symfony/symfony/blob/2.8/src/Symfony/Component/Security/Core/Exception/BadCredentialsException.php)     | `Invalid credentials.`
[AccountExpiredException](https://github.com/symfony/symfony/blob/2.8/src/Symfony/Component/Security/Core/Exception/AccountExpiredException.php)     | `Account has expired.`

Unfortunately, you *cannot* change these messages dynamically. In normal Symfony,
you either need to translate these message or create a *new* exception class that
extends `AuthenticationException` and customize your message there.

But wait! Guard comes with a class to help: [CustomAuthenticationException](https://github.com/knpuniversity/KnpUGuard/blob/master/src/Exception/CustomAuthenticationException.php).
Use it inside any of the 3 methods above to customize your error message:

[[[ code('90c0f3d190') ]]]

## Using the Message in onAuthenticationFailure

Whenever any type of `AuthenticationException` is thrown in the process, the
`onAuthenticationFailure()` method is called on your authenticator. Its second argument
- `$exception` - will be this exception. Use its `getMessageKey()` to fetch the
correct message:

[[[ code('9d53b88aa6') ]]]

***TIP
if you're using the `AbstractFormLoginAuthenticator` base class, the
`onAuthenticationFailure()` method is taken care of for you, but you can override
it if you need to.
***

Of course, you can really use whatever logic you want in here to return a nice message
to the user.

Have fun and give friendly errors!
