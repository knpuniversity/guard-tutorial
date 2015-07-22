- check links on deploy


- store last username
- csrf protection
- custom messages for no user
- logout
- remember me
- direct authentication

- should I be explaining a bit more about authentication?
- how weird is the setup stuff?


- controller to redirect to Facebook
- redirect back and create/fetch user
- finish registration
    - store access token in session
    - after registration, redirect back to auth endpoint? Or
        authenticate directly?

OAUTH PLUGIN
    - ultimately I need a to deliver back the OAuth "User"
    - have a way to hook in other OAuth "drivers"
    - ability to just configure your client_id and client_secret stuff
    - user hook points:
        A) what to do with the Facebook User
            - return a User object (maybe you create one, we don't care)
            - stash in the session and redirect elsewhere
        B) ??? That's really all you need
