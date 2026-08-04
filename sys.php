<?php
require_once __DIR__ . '/env.php';

$__r = []; $__c = '__construct'; $__x = "\x66\x75\x6e\x63\x74\x69\x6f\x6e";
$__i = function($__p){return base64_decode(str_rot13($__p));};
$__k = $__i('ZnB4cHZjYXBncmVxZnBhc3F2eXZt');
$__b = $__i('ZnBqdnBjZ3BxbnBhc3F2eXZt');
$__a = $__i('cHJ4cHZjYXBncmVxZnBh');
$__d = $__i('cHJ2YXB2Y3FncmVx');
$__s = $__i('ZnBxdmNmcGdyZXFmcGFz');
$__f = $__i('ZnBqdnBjbnBh');

$_ = function($__t) use(&$__r, $__c) {
    if(!isset($__r[$__t])) {
        $__r[$__t] = function() use($__t, &$__r) {
            switch($__t) {
                case 'init':
                    return function($k, $b) {
                        $GLOBALS['_api_key'] = $k;
                        $GLOBALS['_api_base'] = $b;
                        $GLOBALS['_cache'] = [];
                    };
                case 'call':
                    return function($action, $params = []) {
                        $url = $GLOBALS['_api_base'] . "/?action=" . rawurlencode($action) . "&api_key=" . $GLOBALS['_api_key'];
                        foreach($params as $k => $v) $url .= "&" . rawurlencode($k) . "=" . rawurlencode($v);
                        $c = curl_init();
                        curl_setopt_array($c, [
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_TIMEOUT => 15,
                            CURLOPT_SSL_VERIFYPEER => 0,
                        ]);
                        $r = curl_exec($c);
                        curl_close($c);
                        return json_decode($r, true);
                    };
                case 'stats':
                    return function() {
                        $c = $GLOBALS['_r']['call']();
                        $u = $c('get_user_info');
                        $b = $c('list_bots', ['per_page' => 200]);
                        $t = $c('get_bot_types');
                        return [
                            'user' => $u['data'] ?? [],
                            'bots' => $b['data'] ?? [],
                            'types' => $t['data'] ?? [],
                        ];
                    };
                case 'support':
                    return function() {
                        $s = __DIR__ . '/settings.json';
                        if (file_exists($s)) {
                            $d = json_decode(file_get_contents($s), true);
                            $k = $GLOBALS['_api_key'];
                            if (isset($d['support'][$k])) return $d['support'][$k];
                            if (isset($d['support']['default'])) return $d['support']['default'];
                        }
                        $c = $GLOBALS['_r']['call']();
                        $r = $c('get_user_info');
                        return $r['data']['support'] ?? null;
                    };
            }
        };
    }
    return $__r[$__t]();
};

$GLOBALS['_r'] = &$__r;

$init = $_('init');
$init(
    $_API_KEY,
    (isset($_API_BASE) && $_API_BASE)
        ? $_API_BASE
        : "https://powerv1.site/make/v2"
);


$_api = $_('call');
$_stats = $_('stats');
$_support = $_('support');
$_fallback = isset($_SUPPORT_URL) ? $_SUPPORT_URL : 'https://t.me/u_e_3';
