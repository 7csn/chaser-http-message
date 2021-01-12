<?php

declare(strict_types=1);

namespace chaser\http\message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * 资源标识
 *
 * @package chaser\http\message
 */
class Uri implements UriInterface
{
    /**
     * 组件库
     *
     * @var array
     */
    protected array $components = [];

    /**
     * 用户信息
     *
     * @var string
     */
    protected string $userInfo;

    /**
     * 授权信息
     *
     * @var string
     */
    protected string $authority;

    /**
     * 协议
     *
     * @var string
     */
    protected string $scheme;

    /**
     * 主机名
     *
     * @var string
     */
    protected string $host;

    /**
     * 端口号
     *
     * @var ?int
     */
    protected ?int $port;

    /**
     * 主路径
     *
     * @var string
     */
    protected string $path;

    /**
     * 查询字符串
     *
     * @var string
     */
    protected string $query;

    /**
     * 片段
     *
     * @var string
     */
    protected string $fragment;

    /**
     * 初始化请求路径
     *
     * @param string $url
     * @throws InvalidArgumentException
     */
    public function __construct(string $url = '')
    {
        if ($url !== '') {
            $components = parse_url($url);
            if ($components) {
                $this->components = $components;
            } else {
                throw new InvalidArgumentException("Unable to parse URI: {$url}");
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): string
    {
        return $this->scheme ??= $this->getComponent('scheme', '');
    }

    /**
     * @inheritDoc
     */
    public function getAuthority(): string
    {
        return $this->authority ??= self::makeAuthority($this->getUserInfo(), $this->getHost(), $this->getPort());
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo(): string
    {
        return $this->userInfo ??= self::makeUserInfo($this->getComponent('user', ''), $this->getComponent('pass'));
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->host ??= $this->getComponent('host', '');
    }

    /**
     * @inheritDoc
     */
    public function getPort(): ?int
    {
        return $this->port ??= $this->getComponent('port');
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path ??= $this->getComponent('path', '');
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return $this->query ??= $this->getComponent('query', '');
    }

    /**
     * @inheritDoc
     */
    public function getFragment(): string
    {
        return $this->fragment ??= $this->getComponent('fragment', '');
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        if ($this->getScheme() === $scheme) {
            return $this;
        }

        Argument::validate('Scheme', $scheme, Argument::STRING);

        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        Argument::validate('User', $user, Argument::STRING);
        Argument::validate('Password', $password, Argument::NULL | Argument::STRING);

        $userInfo = self::makeUserInfo($user, $password);

        if ($userInfo === $this->getUserInfo()) {
            return $this;
        }

        $new = clone $this;
        $this->userInfo = $userInfo;
        $this->setAuthority();
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        if ($this->getHost() === $host) {
            return $this;
        }

        Argument::validate('Host', $host, Argument::STRING);

        $new = clone $this;
        $new->host = $host;
        $new->setAuthority();
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        if ($this->getPort() === $port) {
            return $this;
        }

        Argument::validate('Port', $port, Argument::INT);

        $new = clone $this;
        $new->port = $port;
        $new->setAuthority();
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        if ($this->getPath() === $path) {
            return $this;
        }

        Argument::validate('Path', $path, Argument::STRING);

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        if ($this->getQuery() === $query) {
            return $this;
        }

        Argument::validate('Query', $query, Argument::STRING);

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        if ($this->getFragment() === $fragment) {
            return $this;
        }

        Argument::validate('Fragment', $fragment, Argument::STRING);

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $url = '';

        $scheme = $this->getScheme();
        if ($scheme !== '') {
            $url .= $scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '' || $scheme === 'file') {
            $url .= '//' . $authority;
        }

        $url .= $this->getPath();

        $query = $this->getQuery();
        if ($query !== '') {
            $url .= '?' . $query;
        }

        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    /**
     * 获取组件值
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    protected function getComponent(string $name, $default = null)
    {
        if (isset($this->components[$name])) {
            $component = $this->components[$name];
            unset($this->components[$name]);
            return $component;
        }
        return $default;
    }

    /**
     * 设置组件值
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function setComponent(string $name, $value): self
    {
        if (empty($value)) {
            unset($this->components[$name]);
        } else {
            $this->components[$name] = $value;
        }

        return $this;
    }

    /**
     * 更新授权信息
     */
    protected function setAuthority()
    {
        $this->authority = self::makeAuthority($this->getUserInfo(), $this->getHost(), $this->getPort());
    }

    /**
     * 生成用户信息
     *
     * @param string $user
     * @param string|null $pass
     * @return string
     */
    protected static function makeUserInfo(string $user, ?string $pass): string
    {
        return $user ? $pass ? $user . ':' . $pass : $user : '';
    }

    /**
     * 生成授权信息
     *
     * @param string $userInfo
     * @param string $host
     * @param int|null $port
     * @return string
     */
    protected static function makeAuthority(string $userInfo, string $host, ?int $port): string
    {
        $authority = '';

        if ($userInfo !== '') {
            $authority .= $userInfo . '@';
        }

        $authority .= $host;

        if ($port !== null) {
            $authority .= ':' . $port;
        }

        return $authority;
    }
}
