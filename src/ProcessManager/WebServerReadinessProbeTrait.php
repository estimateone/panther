<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther\ProcessManager;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait WebServerReadinessProbeTrait
{
    /**
     * @throws \RuntimeException
     */
    private function checkPortAvailable(string $hostname, int $port, bool $throw = true): void
    {
        $resource = @fsockopen($hostname, $port);
        if (\is_resource($resource)) {
            fclose($resource);
            if ($throw) {
                throw new \RuntimeException(\sprintf('The port %d is already in use.', $port));
            }
        }
    }

    public function waitUntilReady(Process $process, string $url, bool $ignoreErrors = false): void
    {
        $client =  new Client([
            'timeout'  => 5,
        ]);

        while (true) {
            $status = $process->getStatus();
            if (Process::STATUS_STARTED !== $status) {
                usleep(1000);
                continue;
            }

            try {
                $response = $client->request('GET', $url);
                $ready = 200 === $response->getStatusCode();
            } catch (GuzzleException $e) {
                $ready = false;
            }
            if ($ready) {
                break;
            }
        }
    }
}
