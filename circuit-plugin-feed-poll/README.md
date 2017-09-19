# Circuit Bot Feed Poll Plugin

Plugin for polling feeds. Supporting some ways to authenticate if the feed is protected.

## Authentication currently supported

To use the authentication, add it to `plugins.feed-poll.feeds.[].feed_auth`.

You can combine multiple authentications. (E.g. if you have a form but also require a cookie.)

### Form

POST form data to an URL (`application/x-www-form-urlencoded`).

`feed_auth`-Key: `form`.

Configuration-Keys:

- `auth_url`: Where to POST the form data.
- `form_fields`: key-value-array of form fields to send.

### Cookies

Send cookies with each request.

`feed_auth`-Key: `cookies`.

Configuration-Keys:

- `auth_url`: url to determine cookie-domain from. (Can be used in combination w/ forms)
- `cookies`: key-value-array of cookies. (name => value). Domain will be hostname of `auth_url`.

# Configuration

You can poll any number of feeds, just add more feeds like the one below. (`feeds` is an array of arrays consisting of the keys shown below.)

```
$config = [
    'plugins' => [
        'feed-poll' => [
            'feeds' => [
                [
                    'feed_url' => '',
                    'feed_auth' => [
                        'form',
                        'cookies'
                    ],
                    'auth_url' => '',
                    'form_fields' => [
                        'username' => '',
                        'password' => ''
                    ],
                    'cookies' => [
                        'key' => 'value'
                    ]
                ]
                /* ... other feeds ... */
            ]
        ]
    ]
]

```
