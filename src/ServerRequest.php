<?php

declare(strict_types=1);

namespace chaser\http\message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * http 服务器收到的请求
 *
 * @package chaser\http\message
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * 服务器参数
     *
     * @var array
     */
    protected array $serverParams = [];

    /**
     * cookie 参数
     *
     * @var array
     */
    protected array $cookieParams = [];

    /**
     * 查询字符串参数
     *
     * @var array
     */
    protected array $queryParams = [];

    /**
     * 规范化文件上载数据
     *
     * @var UploadedFileInterface[]
     */
    protected array $uploadedFiles = [];

    /**
     * 正文解析参数
     *
     * @var object|array|null
     */
    protected $parseBody;

    /**
     * 请求派生属性
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * 初始化请求信息
     *
     * @param string $method
     * @param UriInterface $uri
     * @param array|null $serverParams
     * @param array|null $headers
     * @param StreamInterface|null $body
     * @param string|null $protocolVersion
     */
    public function __construct(
        string $method,
        UriInterface $uri,
        array $serverParams = null,
        array $headers = null,
        StreamInterface $body = null,
        string $protocolVersion = null
    )
    {
        if ($serverParams !== null) {
            $this->serverParams = $serverParams;
        }

        parent::__construct($method, $uri, $headers, $body, $protocolVersion);
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies)
    {
        if ($this->cookieParams === $cookies) {
            return $this;
        }

        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        if ($this->queryParams === $query) {
            return $this;
        }

        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        if ($this->uploadedFiles === $uploadedFiles) {
            return $this;
        }

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFileInterface) {
                throw Argument::exception('UploadedFiles', 'an array of UploadedFileInterface instances');
            }
        }

        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parseBody;
    }

    public function withParsedBody($data)
    {
        if ($this->parseBody === $data) {
            return $this;
        }

        Argument::validate('Parsed body', $data, Argument::NULL | Argument::ARRAY | Argument::OBJECT);

        $new = clone $this;
        $new->parseBody = $data;
        return $new;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute($name, $value)
    {
        if (key_exists($name, $this->attributes) && $this->attributes[$name] === $value) {
            return $this;
        }

        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name)
    {
        if (!key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}
