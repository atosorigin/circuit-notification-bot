# Circuit Bot Feed Poll Plugin

Plugin for polling feeds. Supporting some ways to authenticate if the feed is protected.

Please note that this plugin is a little bit specific to Rational-Team-Concert query feeds. Splitting up the RTC and generic functions is TODO.

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
                    // 'conv_id' => '', // optional target conversation. If not set, use default conversation.
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

## RTC

Internally we needed to add an `WASReqURL` cookie for successfull authentication.

Below a function for a RTC feed configuration, just put the function in your `config.php` and use it to configure your feed(s):

```php
function rtc_feed($query)
{
    return [
        // Change [USER]
        'feed_url' => 'https://rtc.example.com/rtc/service/com.ibm.team.repository.common.internal.IFeedService?provider=query&user=[USER]&query=' . $query,
        'feed_auth' => [
            'form',
            'cookies'
        ],
        'auth_url' => 'https://rtc.example.com/rtc/auth/j_security_check',
        'form_fields' => [
            'j_username' => '',
            'j_password' => ''
        ],
        'cookies' => [
            'WASReqURL' => 'https:///rtc/authenticated/identity?redirectPath=%2Frtc' // you may need to change this to match your sub-url (`/rtc`).
        ]
    ];
}
```
