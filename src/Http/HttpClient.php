<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Http\Errors\HttpRequestError;
use Codefy\Framework\Support\ArgsParser;
use Codefy\Framework\Support\RequestMethod;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;

use function func_get_args;
use function Qubus\Security\Helpers\__observer;
use function Qubus\Support\Helpers\is_null__;

/**
 * @phpstan-ignore-next-line
 */
class HttpClient extends GuzzleClient
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public static function factory(): self
    {
        return new self(...func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @param string                    $method  HTTP method.
     * @param string|UriInterface       $uri     URL, URI object or string.
     * @param array<array-key, mixed>   $options {
     *                                     Optional. Array of Request options to apply.
     *                                     See \GuzzleHttp\RequestOptions.
     *
     *      @type string                            $method             Request method. Accepts 'GET', 'POST', 'HEAD',
     *                                                                  'PUT', 'DELETE', 'TRACE', 'OPTIONS', or 'PATCH'.
     *                                                                  Default: 'GET'.
     *      @type float                             $timeout            Float describing the total timeout of the
     *                                                                  request in seconds. Use 0 to wait indefinitely.
     *                                                                  Default 10.
     *      @type float                             $connect_timeout    Float describing the number of seconds to wait
     *                                                                  while trying to connect to a server. Use 0 to
     *                                                                  wait indefinitely. Default 10.
     *      @type bool|array                        $allow_redirects    Describes the redirect behavior of a request.
     *                                                                  Default: false.
     *      @type float|int                         $delay              The number of milliseconds to delay before
     *                                                                  sending the request. Default: null.
     *      @type string                            $version            HTTP protocol version (usually '1.1', '1.0' or
     *                                                                  '2'). Default: '1.1'.
     *      @type bool                              $http_errors        Set to false to disable throwing exceptions on
     *                                                                  an HTTP protocol errors
     *                                                                  (i.e., 4xx and 5xx responses). Default: true.
     *      @type string|array                      $proxy              Whether to enable keep-alive connections with
     *                                                                  the server. Useful and might improve performance
     *                                                                  if several consecutive requests to the same
     *                                                                  server are performed. Default: false.
     *      @type array                             $headers            Array of headers to send with the request.
     *                                                                  Default: [].
     *      @type string|resource|StreamInterface   $body               Used to control the body of an entity enclosing
     *                                                                  request (e.g., PUT, POST, PATCH). Default: ''.
     *      @type bool                              $stream             Set to true to stream a response rather than
     *                                                                  download it all up-front. Default: false.
     *  }
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Exception|GuzzleException
     */
    #[\Override] public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $defaults = [
            /**
             * Filters the total timeout of the request in seconds.
             * Use 0 to wait indefinitely. Default: 10.
             *
             * @param float               $timeout Float describing the number of seconds to
             *                                     wait while trying to connect to a server.
             *                                     Default: 10.
             * @param string|UriInterface $uri     URI object or string.
             */
                'timeout'              => __observer()->filter->applyFilter('http.request.timeout', 10, $uri),
            /**
             * Filters the number of seconds to wait while trying to connect to a server.
             * Use 0 to wait indefinitely. Default: 10.
             *
             * @param float               $connect_timeout Number of seconds. Default: 10.
             * @param string|UriInterface $uri             URI object or string.
             */
                'connect_timeout'      => __observer()->filter->applyFilter('http.request.connect.timeout', 10, $uri),
            /**
             * Filters the version of the HTTP protocol used in a request.
             *
             * @param string              $version HTTP protocol version used (usually '1.1', '1.0' or '2').
             *                                     Default: 1.1.
             * @param string|UriInterface $uri     URI object or string.
             */
                'version'              => __observer()->filter->applyFilter('http.request.version', '1.1', $uri),
            /**
             * Filters the redirect behavior of a request.
             *
             * @param bool|array          $allow_redirects The redirect behavior of a request. Default: false.
             * @param string|UriInterface $uri             URI object or string.
             */
                'allow_redirects '     => __observer()->filter->applyFilter(
                    'http.request.allow.redirects',
                    false,
                    $uri
                ),
                'headers'              => [],
                'body'                 => null,
                'delay'                => null,
                'http_errors'          => true,
                'proxy'                => false,
                'stream'               => false,

        ];

        // Pre-parse for the HEAD checks.
        $options = ArgsParser::parse($options);
        // By default, HEAD requests do not cause redirections.
        if (isset($options['method']) && $options['method'] === RequestMethod::HEAD) {
            $defaults['allow_redirects'] = false;
        }

        $parsedArgs = ArgsParser::parse($options, $defaults);
        /**
         * Filters the arguments used in an HTTP request.
         *
         * @param array               $parsedArgs An array of HTTP request arguments.
         * @param string|UriInterface $uri        URI object or string.
         */
        $parsedArgs = __observer()->filter->applyFilter('http.request.args', $parsedArgs, $uri);
        /**
         * Filters the preemptive return value of an HTTP request.
         *
         * Returning a non-false value from the filter will short-circuit the HTTP request and return
         * early with that value. A filter should return one of:
         *
         *  - An array containing 'headers', 'body', and 'response' elements
         *  - A HttpRequestError instance
         *  - bool false to avoid short-circuiting the response
         *
         * Returning any other value may result in unexpected behavior.
         *
         * @param false|array|HttpRequestError $response   A preemptive return value of an HTTP request. Default false.
         * @param array                        $parsedArgs HTTP request arguments.
         * @param string|UriInterface          $uri        URI object or string.
         */
        $preempt = __observer()->filter->applyFilter('http.request.preempt', false, $parsedArgs, $uri);
        if ($preempt !== false) {
            return $preempt;
        }

        if (is_null__($parsedArgs['headers'])) {
            $parsedArgs['headers'] = [];
        }

        $response = parent::request($method, $uri, $parsedArgs);

        /**
         * Fires after an HTTP API response is received and before the response is returned.
         *
         * @param ResponseInterface|mixed $response   HTTP response.
         * @param string                  $context    Context under which the hook is fired.
         * @param string                  $class      HTTP transport used.
         * @param array                   $parsedArgs HTTP request arguments.
         * @param string|UriInterface     $uri        URI object or string.
         */
        __observer()->action->doAction(
            'http_api_debug',
            $response,
            'response',
            \Qubus\Http\Request::class,
            $parsedArgs,
            $uri
        );

        /**
         * Filters a successful HTTP API response immediately before the response is returned.
         *
         * @param ResponseInterface   $response   HTTP response.
         * @param array               $parsedArgs HTTP request arguments.
         * @param string|UriInterface $uri        URI object or string.
         */
        return __observer()->filter->applyFilter('http.request.response', $response, $parsedArgs, $uri);
    }
}
