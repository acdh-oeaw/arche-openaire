<?php

/*
 * The MIT License
 *
 * Copyright 2019 Austrian Centre for Digital Humanities.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace acdhOeaw\arche\openaire;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use EasyRdf\Resource;
use acdhOeaw\arche\core\RestController as RC;

/**
 * Description of Doorkeeper
 *
 * @author zozlak
 */
class Handlers {

    static public function onGet(int $id, Resource $meta, ?string $path): Resource {
        self::track($id, $meta, false);
        return $meta;
    }

    static public function onGetMetadata(int $id, Resource $meta, ?string $path): Resource {
        self::track($id, $meta, true);
        return $meta;
    }

    static private function track(int $id, Resource $meta, bool $download): void {
        $cfg    = RC::$config->openaire;
        $schema = RC::$config->schema;
        $pid    = (string) $meta->get($schema->pid);
        if (empty($pid)) {
            return;
        }
        $titles = [];
        foreach ($meta->allLiterals($schema->label) as $i) {
            $titles[$i->getLang()] = $i->getValue();
        }
        $title = $titles['en'] ?? $titles['de'] ?? reset($titles);
        $ip = '';
        if ($cfg->trackIp) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $ip = explode(',', $ip);
            $ip = trim(array_pop($ip));
        }
        $ua = $cfg->trackUserAgent ? $_SERVER['HTTP_USER_AGENT'] ?? '' : '';

        // https://openaire.github.io/usage-statistics-guidelines/service-specification/service-spec/
        $param = http_build_query([
            'rec'         => 1,
            'idsite'      => $cfg->id,
            'action_name' => $title,
            'url'         => $download ? '' : RC::getBaseUrl() . $id . '/metadata',
            'urlref'      => str_replace(['{id}', '{baseUrl}'], [$id, RC::getBaseUrl()], $cfg->urlref),
            'download'    => $download ?: RC::getBaseUrl() . $id,
            'cvar'        => $pid,
            'token_auth'  => $cfg->authToken,
            'ua'          => $ua,
            'cip'         => $ip,
        ]);
        $headers  = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $request  = new Request('post', $cfg->url, $headers, $param);
        $client   = new Client(['http_errors' => false]);
        $response = $client->send($request);

        $status = $response->getStatusCode();
        $msg    = "OpenAIRE tracked with $cfg->url?$param";
        if ($status >= 200 && $status < 300) {
            RC::$log->debug($msg);
        } else {
            RC::$log->error($msg);
        }
    }
}
