<?php

if(!function_exists('wakeup_feed'))
{

    global $hooks;

    $hooks->add_action(ACTION_PLG_INIT, 'feed_init');

    function feed_init()
    {
        global $plugin_states;

        $plugin_states['ciis0.feed-poll'] = [
            'msg_ids' => [],
            'mtl' => [], // message to link
            'stor' => new ICanBoogie\Storage\FileStorage(__DIR__ . DIRECTORY_SEPARATOR . 'stor'), // stor = store (no typo here)
        ];
    }

    $hooks->add_action('wakeup_advanced', 'wakeup_feed');

    function wakeup_feed()
    {

        global $config;
        global $plugin_states;

        $my_config = $config['plugins']['feed_poll'];
        $my_state = &$plugin_states['ciis0.feed-poll'];

        foreach($my_config['feeds'] as $my_feed)
        {

            $feed_url = $my_feed['feed_url'];
            $feed_auth = $my_feed['feed_auth'];
            $auth_url = $my_feed['auth_url'];
            $conv_id = null;

            if(isset($my_feed['conv_id']))
            {
                $conv_id = $my_feed['conv_id'];
            }

            $storage = $my_state['stor'];
            $feed_mri_token = 'mri_' . sha1($feed_url); // mri most recent id; hash to sanitize

            $client_cfg = [];

            if(in_array('cookies', $feed_auth))
            {
                $cookies = $my_feed['cookies'];
                $client_cfg['defaults'] = [
                    'cookies' => GuzzleHttp\Cookie\CookieJar::fromArray($cookies, parse_url($auth_url, PHP_URL_HOST))
                ];
            }

            $client = new GuzzleHttp\Client($client_cfg);

            if(in_array('form', $feed_auth))
            {
                $client->post($auth_url, [
                    'body' => $my_feed['form_fields'],
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

                    $link = $item->get_link(0);

                    $mes = new AdvancedMessage(
                        $item->get_title() . ': ' . preg_replace('/\\s+/', ' ', $item->get_description()), // circuit does not like line breaks
                        $storage->retrieve('ltp_' . sha1($link)) // ltp link to parent
                    );

                    $my_state['msg_ids'][] = $mes->id;
                    $my_state['mtl'][$mes->id] = $link;

                    if(isset($conv_id))
                    {
                        $mes->conv_id = $conv_id;
                    }

                    circuit_send_message_adv($mes);
                }
                $mri = $id0;
            }
            // else nothing to do

            $storage->store($feed_mri_token, $mri);
        }
    }

    $hooks->add_action(ACTION_PARENT_ID, 'parent_id_feed', 10 /* default priority */, 2);

    function parent_id_feed($msg_id, $item_id)
    {
        global $plugin_states;
        $my_state = $plugin_states['ciis0.feed-poll'];

        if(in_array($msg_id, $my_state['msg_ids']))
        {
            echo "Feed: Message {$msg_id} is ours!", PHP_EOL;
            $my_state['stor']->store('ltp_' .  sha1($my_state['mtl'][$msg_id]), $item_id); // ltp link to parent, hash to sanitize link (url)
        }
        else
        {
            echo "Feed: Message {$msg_id} not ours!", PHP_EOL;
        }
    }

}
