<?php

declare(strict_types=1);

namespace Mezzio\Router\LaminasRouter;

use Laminas\Stdlib\Request;
use Laminas\Uri\Exception\InvalidUriPartException;
use Laminas\Uri\Http as HttpUri;
use Psr\Http\Message\ServerRequestInterface;

use function basename;
use function dirname;
use function in_array;
use function is_array;
use function is_string;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function trim;

use const PHP_SAPI;

/**
 * Minimal request for laminas-router, created from a PSR-7 server request.
 *
 * The routes shipped with laminas-router only consume the URI, the HTTP method
 * and the base URL of a request. Exposing exactly those removes the need to
 * convert each PSR-7 request to a laminas-http request before matching.
 */
final class RouterRequest extends Request
{
    private readonly HttpUri $uri;

    private readonly string $method;

    private readonly string $requestUri;

    /** @var array<array-key, mixed> */
    private readonly array $serverParams;

    private ?string $baseUrl = null;

    /**
     * @throws InvalidUriPartException If the URI of the request is not a valid HTTP URI.
     */
    public function __construct(ServerRequestInterface $request)
    {
        $uri = (string) $request->getUri();

        $this->uri          = new HttpUri($uri);
        $this->method       = strtoupper($request->getMethod());
        $this->requestUri   = preg_replace('#^[^/:]+://[^/]+#', '', $uri) ?? '';
        $this->serverParams = $request->getServerParams();
    }

    public function getUri(): HttpUri
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Base URL of the application: anything additional to the document root.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl ??= rtrim($this->detectBaseUrl(), '/');
    }

    /**
     * Detects the base URL the same way laminas-http does for a PHP environment request.
     */
    private function detectBaseUrl(): string
    {
        $filename       = $this->serverParam('SCRIPT_FILENAME') ?? '';
        $scriptName     = $this->serverParam('SCRIPT_NAME');
        $phpSelf        = $this->serverParam('PHP_SELF');
        $origScriptName = $this->serverParam('ORIG_SCRIPT_NAME');

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion matching PHP_SELF.
            // Only for CLI requests argv[0] contains the script filename.
            if (PHP_SAPI === 'cli') {
                $script = self::firstArgument($this->serverParams['argv'] ?? null);
                if ($script !== null && $script !== '' && str_starts_with($filename, $script)) {
                    $filename = substr($filename, strlen($script));
                }
            }

            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename !== '' && $basename !== '0') {
                $path     = $phpSelf !== null && $phpSelf !== '' && $phpSelf !== '0' ? trim($phpSelf, '/') : '';
                $basePos  = (int) strpos($path, $basename);
                $baseUrl .= substr($path, 0, $basePos) . $basename;
            }
        }

        if ($baseUrl === '' || $baseUrl === '0') {
            return '';
        }

        // Full base URL matches.
        if (str_starts_with($this->requestUri, $baseUrl)) {
            return $baseUrl;
        }

        // Directory portion of the base URL matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (str_starts_with($this->requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $this->requestUri;
        $queryPos            = strpos($this->requestUri, '?');
        if ($queryPos !== false) {
            $truncatedRequestUri = substr($this->requestUri, 0, $queryPos);
        }

        $basename = basename($baseUrl);
        if (in_array($basename, ['', '0'], true) || ! str_contains($truncatedRequestUri, $basename)) {
            return '';
        }

        // When a rewrite is in place, strip the script filename out of the base
        // URL. A position other than 0 makes sure the match does not come from
        // PATH_INFO or QUERY_STRING.
        $pos = strpos($this->requestUri, $baseUrl);
        if ($pos !== false && $pos !== 0) {
            $baseUrl = substr($this->requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    private function serverParam(string $name): ?string
    {
        return self::stringOrNull($this->serverParams[$name] ?? null);
    }

    private static function firstArgument(mixed $argv): ?string
    {
        return is_array($argv) ? self::stringOrNull($argv[0] ?? null) : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
