<?php

declare(strict_types=1);

namespace chaser\http\message\traits;

use chaser\http\message\Argument;
use chaser\http\message\StreamWithString;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    protected string $protocolVersion = '1.1';

    /**
     * 虚（纯小写）实消息头名称对照表
     *
     * @var array
     */
    protected array $headerNames = [];

    protected array $headers = [];

    protected StreamInterface $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        return $this->protocolVersion === $version ? $this : (clone $this)->setProtocolVersion((string)$version);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        $name = strtolower($name);
        return isset($this->headerNames[$name]) ? $this->headers[$this->headerNames[$name]] : [];
    }

    public function getHeaderLine($name): string
    {
        return join(', ', $this->getHeader($name));
    }

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

    public function withAddedHeader($name, $value)
    {
        Argument::validate('Header name', $name, Argument::STRING);

        return (clone $this)->setHeader($name, $value);
    }

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

    public function getBody(): StreamInterface
    {
        return $this->body ??= new StreamWithString();
    }

    public function withBody(StreamInterface $body)
    {
        return $this->getBody() === $body ? $this : (clone $this)->setBody($body);
    }

    private function setProtocolVersion(string $protocolVersion): self
    {
        $this->protocolVersion = $protocolVersion;
        return $this;
    }

    private function setHeaders(array $headers): self
    {
        array_walk($headers, function ($value, $name) {
            $this->setHeader($name, $value);
        });
        return $this;
    }

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
                throw new InvalidArgumentException('Header value can not be an empty array');
            }
            $values = $value;
        } else {
            $values = [$value];
        }

        return array_map(function ($value) {
            if (is_string($value)) {
                return trim($value, " \t");
            }
            throw new InvalidArgumentException('Header value must be an array of non empty strings');
        }, $values);
    }
}
