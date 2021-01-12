<?php

declare(strict_types=1);

namespace chaser\http\message;

use chaser\http\message\traits\MessageTrait;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * http 客户端请求
 *
 * @package chaser\http\message
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * 可用请求方法
     *
     * @var string[]
     */
    public const ALLOW_METHODS = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * URI 对象
     *
     * @var UriInterface
     */
    protected UriInterface $uri;

    /**
     * 请求方法类型
     *
     * @var string
     */
    protected string $method;

    /**
     * 请求目标
     *
     * @var string
     */
    protected string $target;

    /**
     * 初始化请求信息
     *
     * @param string $method
     * @param UriInterface $uri
     * @param array|null $headers
     * @param StreamInterface|null $body
     * @param string|null $protocolVersion
     */
    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = null,
        StreamInterface $body = null,
        string $protocolVersion = null
    )
    {
        if ($headers !== null) {
            $this->setHeaders($headers);
        }
        if ($body !== null) {
            $this->setBody($body);
        }
        if ($protocolVersion !== null) {
            $this->setProtocolVersion($protocolVersion);
        }

        $this->setMethod($method)->setUri($uri, $this->hasHeader('Host'));
    }

    public function getRequestTarget(): string
    {
        if ($this->target === null) {

            $this->target = $this->uri->getPath() ?: '/';

            $query = $this->uri->getQuery();

            if ($query !== '') {
                $this->target .= '?' . $query;
                $fragment = $this->uri->getFragment();
                if ($fragment !== '') {
                    $this->target .= '#' . $fragment;
                }
            }
        }

        return $this->target;
    }

    public function withRequestTarget($requestTarget)
    {
        if ($this->target === $requestTarget) {
            return $this;
        }

        $new = clone $this;
        $new->target = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        if (!is_string($method) || $method === '') {
            throw  new InvalidArgumentException('Method must be a non empty string');
        }

        return $this->method === $method ? $this : (clone $this)->setMethod($method);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->uri === $uri ? $this : (clone $this)->setUri($uri, $preserveHost);
    }

    public function __toString(): string
    {
        $requestLine = "{$this->getMethod()} {$this->getRequestTarget()} HTTP/{$this->getProtocolVersion()}";

        $headers = [];
        foreach ($this->getHeaders() as $name => $header) {
            $headers = $name . '=' . join('; ', $header);
        }

        return $requestLine . "\r\n" . join("\r\n", $headers) . "\r\n\r\n" . $this->getBody();
    }

    /**
     * 设置请求方法
     *
     * @param string $method
     * @return $this
     * @throws InvalidArgumentException
     */
    private function setMethod(string $method): self
    {
        if ($this->isMethodInvalid($method)) {
            throw new InvalidArgumentException('Invalid path provided for request');
        }

        $this->method = $method;
        return $this;
    }

    /**
     * 设置 URI
     *
     * @param UriInterface $uri
     * @param bool $preserveHost
     * @return $this
     */
    private function setUri(UriInterface $uri, bool $preserveHost = false): self
    {
        $this->uri = $uri;
        return $preserveHost ? $this : $this->setHostFromUri();
    }

    /**
     * 通过 URI 设置主机头
     *
     * @return $this
     */
    private function setHostFromUri(): self
    {
        $host = $this->uri->getHost();

        if ($host !== '') {

            $port = $this->uri->getPort();
            if ($port !== null) {
                $host .= ':' . $port;
            }

            if (isset($this->headerNames['host'])) {
                $name = $this->headerNames['host'];
            } else {
                $this->headerNames['host'] = $name = 'Host';
            }

            // 将主机头信息排在最前
            $this->headers = [$name => [$host]] + $this->headers;
        }

        return $this;
    }

    /**
     * 判断请求方法是否无效
     *
     * @param string $method
     * @return bool
     */
    private function isMethodInvalid(string $method): bool
    {
        return in_array($method, self::ALLOW_METHODS);
    }
}
