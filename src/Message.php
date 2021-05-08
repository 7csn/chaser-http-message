<?php

declare(strict_types=1);

namespace chaser\http\message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * 消息特征
 *
 * @package chaser\http\message
 */
trait Message
{
    /**
     * HTTP 协议版本
     *
     * @var string
     */
    private string $protocolVersion = '1.1';

    /**
     * 虚（纯小写）实消息头名称对照表
     *
     * @var array
     */
    private array $headerNames = [];

    /**
     * 消息头数组
     *
     * @var string[][]
     */
    private array $headers = [];

    /**
     * 消息体流
     *
     * @var StreamInterface
     */
    private StreamInterface $body;

    /**
     * 返回 HTTP 协议版本
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * 返回具有指定 HTTP 协议版本的实例
     *
     * @param string $version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        return $this->protocolVersion === $version ? $this : (clone $this)->setProtocolVersion($version);
    }

    /**
     * 返回消息头数组
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 返回是否存在指定（不区分大小写）名称的消息头
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader($name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * 返回指定（不区分大小写）名称的消息头
     *
     * @param string $name
     * @return array
     */
    public function getHeader($name): array
    {
        $name = strtolower($name);
        return isset($this->headerNames[$name]) ? $this->headers[$this->headerNames[$name]] : [];
    }

    /**
     * 返回指定（不区分大小写）名称的消息头（逗号分隔）字符串
     *
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name): string
    {
        return join(', ', $this->getHeader($name));
    }

    /**
     * 返回具有指定消息头的实例
     *
     * @param string $name
     * @param string|string[] $value
     * @return static
     */
    public function withHeader($name, $value)
    {
        Argument::validate('Header name', $name, Argument::STRING);

        $value = self::normalizeHeaderValue($value);

        $lowCaseName = strtolower($name);

        $new = clone $this;

        if (isset($new->headerNames[$lowCaseName])) {
            unset ($new->headers[$new->headerNames[$lowCaseName]]);
        }

        $new->headerNames[$lowCaseName] = $name;
        $new->headers[$name] = $value;

        return $new;
    }

    /**
     * 返回附加指定消息头的实例
     *
     * @param $name
     * @param string|string[] $value
     * @return static
     */
    public function withAddedHeader($name, $value)
    {
        Argument::validate('Header name', $name, Argument::STRING);

        return (clone $this)->setHeader($name, $value);
    }

    /**
     * 返回没有指定消息头的实例
     *
     * @param string $name
     * @return static
     */
    public function withoutHeader($name)
    {
        $lowCaseName = strtolower($name);

        if (!isset($this->headerNames[$lowCaseName])) {
            return $this;
        }

        $new = clone $this;
        unset($new->headers[$new->headerNames[$lowCaseName]], $new->headerNames[$lowCaseName]);
        return $new;
    }

    /**
     * 获取消息体
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->body ??= Stream::create();
    }

    /**
     * 返回具有指定消息体的实例
     *
     * @param StreamInterface $body
     * @return static
     */
    public function withBody(StreamInterface $body)
    {
        return $this->getBody() === $body ? $this : (clone $this)->setBody($body);
    }

    /**
     * 修改 HTTP 协议版本
     *
     * @param string $version
     * @return $this
     */
    private function setProtocolVersion(string $version): self
    {
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * 批量修改消息头
     *
     * @param array $headers
     * @return $this
     */
    private function setHeaders(array $headers): self
    {
        array_walk($headers, function ($value, $name) {
            $this->setHeader($name, $value);
        });
        return $this;
    }

    /**
     * 修改指定消息头
     *
     * @param string $name
     * @param string|string[] $value
     * @return $this
     */
    private function setHeader(string $name, $value): self
    {
        $value = self::normalizeHeaderValue($value);

        $lowCaseName = strtolower($name);

        if (isset($this->headerNames[$lowCaseName])) {
            $name = $this->headerNames[$lowCaseName];
            $this->headers[$name] = array_merge($this->headers[$name], $value);
        } else {
            $this->headerNames[$lowCaseName] = $name;
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * 修改消息体
     *
     * @param StreamInterface $body
     * @return $this
     */
    private function setBody(StreamInterface $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * 剔除头部值数组元素中的空格及制表符（验证非空字符串）
     *
     * @param mixed $value
     * @return string[]
     * @throws InvalidArgumentException
     */
    private static function normalizeHeaderValue($value): array
    {
        if (is_array($value)) {
            if (count($value) === 0) {
                throw new InvalidArgumentException('Header value can not be an empty array.');
            }
            $values = $value;
        } else {
            $values = [$value];
        }

        return array_map(function ($value) {
            if (is_string($value)) {
                return trim($value, " \t");
            }
            throw new InvalidArgumentException('Header value must be an array of non empty strings.');
        }, $values);
    }
}
