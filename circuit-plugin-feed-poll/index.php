<?php

if(!function_exists('wakeup_feed'))
{

    global $hooks;

    $hooks->add_filter('wakeup', 'wakeup_feed');

    function wakeup_feed($ary)
    {

        global $config;

        $my_config = $config['plugins']['feed_poll'];

        $feed_url = $my_config['feed_url'];
        $feed_auth = $my_config['feed_auth'];
        $auth_url = $my_config['auth_url'];

        $storage = new ICanBoogie\Storage\FileStorage(__DIR__);
        $feed_mri_token = 'mri_' . sha1($feed_url); // mri most recent id; hash to sanitize

        $client_cfg = [];

        if(in_array('cookies', $feed_auth))
        {
            $cookies = $my_config['cookies'];
            $client_cfg['defaults'] = [
                'cookies' => GuzzleHttp\Cookie\CookieJar::fromArray($cookies, parse_url($auth_url, PHP_URL_HOST))
            ];
        }

        $client = new GuzzleHttp\Client($client_cfg);

        if(in_array('form', $feed_auth))
        {
            $client->post($auth_url, [
                'body' => $my_config['form_fields'],
                'allow_redirects' => true
            ]);
        }

        $response = $client->get($feed_url);

        $feed = new SimplePie();

        $feed->set_raw_data((string) ($response->getBody()));
        $feed->init();

        $mri = $storage->retrieve($feed_mri_token);
        $id0 = $feed->get_item(0)->get_id();

        if($id0 != $mri)
        {
            foreach ($feed->get_items() as $item)
            {
                if($item->get_id() == $mri) break;
                $ary[] = $item->get_title() . ': ' . preg_replace('/\\s+/', ' ', $item->get_description()); // circuit does not like line breaks
            }
            $mri = $id0;
        }
        // else nothing to do

        $storage->store($feed_mri_token, $mri);
        return $ary;
    }

}
