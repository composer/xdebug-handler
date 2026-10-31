<?php

declare(strict_types=1);

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests\App\Framework;

class MessageProvider
{
    /** @var bool */
    private $extended = false;

    /** @var array<string, string[]> */
    private $items = [];

    public function __construct(string $envAllow, bool $extended)
    {
        // type: array of phrases to check for
        $this->items = [
            'check'         => ['Checking ', $envAllow],
            'loaded'        => ['The Xdebug extension is loaded'],
            'not-loaded'    => ['The Xdebug extension is not loaded'],
            'restarting'    => ['Process restarting', $envAllow.'='],
            'run-std'       => ['Running', PHP_BINARY, '-n', '-c'],
            'run-persist'   => ['Running', PHP_BINARY, '!-n', '!-c'],
            'exit'          => ['Restarted process exited 0'],
            'restarted'     => ['Restarted (', 'ms). The Xdebug extension is not loaded'],
            'existing'      => ['Process called with existing restart settings'],
            'allow'         => ['No restart', $envAllow.'=1'],
            'allow-app'     => ['No restart', $envAllow.'=', 'Allowed by application'],
            'allow-mode'    => ['No restart', $envAllow.'=', 'Allowed by xdebug.mode'],
            'error'         => ['No restart'],
        ];

        if ($extended) {
            $this->extended = true;
            $first = ['extend' => ['Using extending class']];
            $this->items = array_merge($first, $this->items);
        }
    }

    /**
     * @param string[] $types
     * @param array<string, string[]> $extras
     * @return array<string, string[]>
     */
    public function compose(array $types, ?array $extras = null): array
    {
        $result = [];

        if ($this->extended && ($types[0] ?? null) === 'check') {
            array_unshift($types, 'extend');
        }

        foreach ($types as $key) {
            if (!isset($this->items[$key])) {
                // Look for extras
                if (isset($extras[$key]) && is_array($extras[$key])) {
                    $result[$key] = $extras[$key];
                    continue;
                }
                throw new \LogicException('Message type not found: '.$key);
            }
            $result[$key] = $this->items[$key];
        }

        return $result;
    }

    /**
     * @return array<array<string, string[]>, array<string, string[]>>
     */
    public function getRestartMessages(bool $persistent = false): array
    {
        [$main, $child] = $this->getRestartTypes($persistent);
        return [$this->compose($main), $this->compose($child)];
    }

    /**
     * @return array<string, string[]>
     */
    public function getNoRestartMessages(?string $allow = null): array
    {
        $types = $this->getNoRestartTypes($allow);
        return $this->compose($types);
    }

    /**
     * @return array<string, string[]>
     */
    public function getExistingMessages(): array
    {
        $types = ['check', 'not-loaded', 'existing'];
        return $this->compose($types);
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrorMessages(string $error): array
    {
        $types = ['check', 'loaded', 'error'];
        $result = $this->compose($types);
        $result['error'][] = $error;

        return $result;
    }

    /**
     * @return array<string[], string[]>
     */
    public function getRestartTypes(bool $persistent = false): array
    {
        $main = [
            'check',
            'loaded',
            'restarting',
            $persistent ? 'run-persist' : 'run-std',
            'exit',
        ];

        return [$main, ['check', 'restarted']];
    }

    /**
     * @return string[]
     */
    public function getNoRestartTypes(?string $allow = null): array
    {
        $types = ['check'];

        if ($allow === null) {
            $types[] = 'not-loaded';
        } else {
            $types[] = 'loaded';
            $types[] = $allow;
        }

        return $types;
    }
}
