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

        if (null === $saveName = $this->getHeaderNameByLowCaseName($name)) {
            return [];
        }

        return $this->headers[$saveName];
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

        return (clone $this)->setHeader($name, $value);
    }

    /**
     * 返回附加指定消息头的实例
     *
     * @param string $name
     * @param string|string[] $value
     * @return static
     */
    public function withAddedHeader($name, $value)
    {
        Argument::validate('Header name', $name, Argument::STRING);

        return (clone $this)->addHeader($name, $value);
    }

    /**
     * 返回没有指定消息头的实例
     *
     * @param string $name
     * @return static
     */
    public function withoutHeader($name)
    {
        Argument::validate('Header name', $name, Argument::STRING);

        $lowCaseName = strtolower($name);

        return isset($this->headerNames[$lowCaseName]) ? (clone $this)->delHeaderByLowCaseName($lowCaseName) : $this;
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
     * 通过小写名获取当前消息头名
     *
     * @param string $name
     * @return string|null
     */
    private function getHeaderNameByLowCaseName(string $name): ?string
    {
        return $this->headerNames[$name] ?? null;
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

        $saveName = $this->getHeaderNameByLowCaseName($lowCaseName);

        if ($saveName !== $name) {
            $this->headerNames[$lowCaseName] = $name;
        }

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * 添加指定消息头
     *
     * @param string $name
     * @param string|string[] $value
     * @return $this
     */
    private function addHeader(string $name, $value): self
    {
        $value = self::normalizeHeaderValue($value);

        $lowCaseName = strtolower($name);

        $saveName = $this->getHeaderNameByLowCaseName($lowCaseName);

        if ($saveName === null) {
            $this->headerNames[$lowCaseName] = $name;
            $this->headers[$name] = $value;
        } else {
            $this->headers[$name] = array_merge($this->headers[$saveName], $value);
        }

        return $this;
    }

    /**
     * 通过小写名删除消息头
     *
     * @param string $name
     * @return $this
     */
    private function delHeaderByLowCaseName(string $name): self
    {
        if (null !== $saveName = $this->getHeaderNameByLowCaseName($name)) {
            unset($this->headers[$saveName], $this->headerNames[$name]);
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
     * 头部值视为字符串数组（验证非空），剔除元素空格及制表符
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
            return trim((string)$value, " \t");
        }, $values);
    }
}
