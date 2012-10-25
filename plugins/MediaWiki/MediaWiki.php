<?php

namespace Minion\Plugins;

$Wiki = new \Minion\Plugin(
    'MediaWiki',
    'Plugin to interface with a MediaWiki installation.',
    'Ryan N. Freebern / ryan@freebern.org'
);

return $Wiki

// RPL_ENDOFMOTD
->on('before-loop', function (&$data) use ($Wiki) {
    $Wiki->s = curl_init();

    curl_setopt($Wiki->s, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($Wiki->s, CURLOPT_TIMEOUT, 10);
    curl_setopt($Wiki->s, CURLOPT_MAXREDIRS, 5);
    curl_setopt($Wiki->s, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Wiki->s, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($Wiki->s, CURLOPT_COOKIEJAR, $Wiki->conf('curlCookieFile'));
    curl_setopt($Wiki->s, CURLOPT_COOKIEFILE, $Wiki->conf('curlCookieFile'));

    if ($Wiki->conf('HTTPAuth')) {
        curl_setopt($Wiki->s, CURLOPT_USERPWD, $Wiki->conf('HTTPAuth/Username') . ':' . $Wiki->conf('HTTPAuth/Password'));
    }

    // Log in
    curl_setopt($Wiki->s, CURLOPT_URL, $Wiki->conf('WikiURL') . '/api.php?action=login&format=json');
    $post = 'action=login&lgname=' . urlencode($Wiki->conf('Username')) . '&lgpassword=' . urlencode($Wiki->conf('Password'));
    curl_setopt($Wiki->s, CURLOPT_POSTFIELDS, $post);

    $response = curl_exec($Wiki->s);

    if ($response) {
        $loginObject = json_decode($response);
        curl_setopt($Wiki->s, CURLOPT_POSTFIELDS, $post . '&lgtoken=' . urlencode($loginObject->login->token));
        $response = curl_exec($Wiki->s);
        if (!$response) {
            throw new RuntimeException("Couldn't log in to mediawiki.");
        }

        curl_setopt($Wiki->s, CURLOPT_POSTFIELDS, null);
    }
})

->on('PRIVMSG', function (&$data) use ($Wiki) {
    list ($command, $arguments) = $Wiki->simpleCommand($data);
    if ($command == 'wiki') {
        $target = $data['arguments'][0];
        if ($target == $Wiki->Minion->State['Nickname']) {
            list ($target, $ident) = explode('!', $data['source']);
        }
        if (count($arguments)) {
            $search = implode(' ', $arguments);
            curl_setopt($Wiki->s, CURLOPT_URL, $Wiki->conf('WikiURL') . '/api.php?action=query&list=search&srwhat=title&format=json&srsearch=' . urlencode($search));
            $response = curl_exec($Wiki->s);
            if ($response) {
                $respObj = json_decode($response);
                if (!is_null($respObj) and is_object($respObj) and property_exists($respObj, 'query') and is_object($respObj->query) and property_exists($respObj->query, 'search') and is_array($respObj->query->search) and count($respObj->query->search)) {
                    curl_setopt($Wiki->s, CURLOPT_URL, $Wiki->conf('WikiURL') . '/api.php?action=query&prop=info&inprop=url&format=json&titles=' . urlencode($respObj->query->search[0]->title));
                    $response = curl_exec($Wiki->s);
                    if ($response) {
                        $respObj = json_decode($response);
                        if (!is_null($respObj) and is_object($respObj) and property_exists($respObj, 'query') and is_object($respObj->query) and property_exists($respObj->query, 'pages') and is_object($respObj->query->pages)) {
                            $pages = get_object_vars($respObj->query->pages);
                            foreach ($pages as $pageID => $page) {
                                $Wiki->Minion->msg("{$page->title} {$page->fullurl}", $target);
                            }
                        }
                    }
                }
            }
        } else {
            $Wiki->Minion->msg($Wiki->conf('WikiURL'), $target);
        }
    }
});

?>
