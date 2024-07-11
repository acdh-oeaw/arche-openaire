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
use GuzzleHttp\Exception\TransferException;
use rdfInterface\DatasetNodeInterface;
use rdfInterface\LiteralInterface;
use termTemplates\PredicateTemplate as PT;
use acdhOeaw\arche\core\RestController as RC;

/**
 * Description of Doorkeeper
 *
 * @author zozlak
 */
class Handlers {

    const DEFAULT_TIMEOUT = 1;

    static public function onGet(int $id, DatasetNodeInterface $meta,
                                 ?string $path): DatasetNodeInterface {
        self::track($id, $meta, false);
        return $meta;
    }

    static public function onGetMetadata(int $id, DatasetNodeInterface $meta,
                                         ?string $path): DatasetNodeInterface {
        self::track($id, $meta, true);
        return $meta;
    }

    static private function track(int $id, DatasetNodeInterface $meta,
                                  bool $download): void {
        $cfg = RC::$config->openaire;
        if (empty($cfg->authToken ?? '')) {
            return;
        }
        $schema = RC::$schema;
        $pid    = self::getPid($id, $meta);
        if (empty($pid)) {
            return;
        }
        $titles = [];
        foreach ($meta->listObjects(new PT($schema->label)) as $i) {
            $lang          = $i instanceof LiteralInterface ? $i->getLang() : '';
            $titles[$lang] = $i->getValue();
        }
        $title = $titles['en'] ?? $titles['de'] ?? reset($titles);
        $url   = RC::getBaseUrl() . $id . ($download ? '' : '/metadata');

        // https://openaire.github.io/usage-statistics-guidelines/service-specification/service-spec/
        $param = [
            'rec'         => 1,
            'idsite'      => $cfg->id,
            'action_name' => $title,
            'url'         => $url,
            'urlref'      => $_SERVER['HTTP_REFERER'] ?? '',
            'cvar'        => json_encode(['1' => ["PID", $pid]], JSON_UNESCAPED_SLASHES),
            'token_auth'  => $cfg->authToken,
        ];
        if ($download) {
            $param['download'] = $url;
        }
        if ($cfg->trackIp) {
            $ip           = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $ip           = explode(',', $ip);
            $param['cip'] = trim(array_pop($ip));
        }
        if ($cfg->trackUserAgent) {
            $param['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        $param   = http_build_query($param);
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $request = new Request('post', $cfg->url, $headers, $param);
        $client  = new Client([
            'http_errors' => false,
            'timeout'     => $cfg->timeout ?? self::DEFAULT_TIMEOUT,
        ]);
        try {
            $response = $client->send($request);

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                RC::$log->debug("OpenAIRE tracked with " . $request->getUri());
            } else {
                RC::$log->error("OpenAIRE tracking failed with response code $status (" . $request->getUri() . ")");
            }
        } catch (TransferException $e) {
            RC::$log->error("OpenAIRE tracking failed with " . $e->getMessage() . " (" . $request->getUri() . ")");
        }
    }

    static private function getPid(int $id, DatasetNodeInterface $meta): string {
        $query = RC::$config->openaire->pidQuery ?? '';
        if (empty($query)) {
            return (string) $meta->getObject(new PT(RC::$config->schema->pid));
        }
        $param = (array) (RC::$config->openaire->pidQueryParam ?? []);
        $param = array_map(fn($x) => $x === '{id}' ? $id : $x, $param);
        $query = RC::$pdo->prepare($query);
        $query->execute($param);
        return (string) $query->fetchColumn();
    }
}
