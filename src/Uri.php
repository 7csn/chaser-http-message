<?php

declare(strict_types=1);

namespace chaser\http\message;

use chaser\utils\validation\Type;
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
     * 用户信息
     *
     * @var string|null
     */
    private ?string $userInfo;

    /**
     * 授权信息
     *
     * @var string|null
     */
    private ?string $authority;

    /**
     * 协议
     *
     * @var string
     */
    private string $scheme;

    /**
     * 主机名
     *
     * @var string
     */
    private string $host;

    /**
     * 端口号
     *
     * @var ?int
     */
    private ?int $port;

    /**
     * 路径
     *
     * @var string
     */
    private string $path;

    /**
     * 查询字符串
     *
     * @var string
     */
    private string $query;

    /**
     * 片段
     *
     * @var string
     */
    private string $fragment;

    /**
     * 组件库
     *
     * @var array
     */
    private array $components = [];

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
                throw new InvalidArgumentException(sprintf('Unable to parse URI: %s.', $url));
            }
        }
    }

    /**
     * 检索方案组件
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme ??= strtolower($this->getComponent('scheme', ''));
    }

    /**
     * 检索授权组件
     *
     * @return string
     */
    public function getAuthority(): string
    {
        return $this->authority ??= self::makeAuthority($this->getUserInfo(), $this->getHost(), $this->getPort());
    }

    /**
     * 检索用户信息组件
     *
     * @return string
     */
    public function getUserInfo(): string
    {
        return $this->userInfo ??= self::makeUserInfo($this->getComponent('user', ''), $this->getComponent('pass'));
    }

    /**
     * 检索主机组件
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host ??= $this->getComponent('host', '');
    }

    /**
     * 获取端口组件
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port ??= $this->getComponent('port');
    }

    /**
     * 检索路径组件
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path ??= $this->getComponent('path', '');
    }

    /**
     * 检索查询字符串
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query ??= $this->getComponent('query', '');
    }

    /**
     * 检索片段组件
     *
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment ??= $this->getComponent('fragment', '');
    }

    /**
     * 返回具有指定方案的实例
     *
     * @param string $scheme
     * @return static
     * @throws InvalidArgumentException
     */
    public function withScheme($scheme): self
    {
        Type::validate('Scheme', $scheme, Type::STRING);

        return $this->getScheme() === $scheme ? $this : (clone $this)->setScheme($scheme);
    }

    /**
     * 返回具有指定用户信息的实例
     *
     * @param string $user
     * @param string|null $password
     * @return static
     * @throws InvalidArgumentException
     */
    public function withUserInfo($user, $password = null): self
    {
        Type::validate('User', $user, Type::STRING);
        Type::validate('Password', $password, Type::STRING | Type::NULL);

        $userInfo = self::makeUserInfo($user, $password);

        return $this->getUserInfo() === $userInfo ? $this : (clone $this)->setUserInfo($userInfo);
    }

    /**
     * 返回具有指定主机的实例
     *
     * @param string $host
     * @return static
     * @throws InvalidArgumentException
     */
    public function withHost($host): self
    {
        Type::validate('Host', $host, Type::STRING);

        return $this->getHost() === $host ? $this : (clone $this)->setHost($host);
    }

    /**
     * 返回具有指定端口的实例
     *
     * @param int|null $port
     * @return static
     * @throws InvalidArgumentException
     */
    public function withPort($port): self
    {
        Type::validate('Port', $port, Type::INT | Type::NULL);

        return $this->getPort() === $port ? $this : (clone $this)->setPort($port);
    }

    /**
     * 返回具有指定路径的实例
     *
     * @param string $path
     * @return static
     * @throws InvalidArgumentException
     */
    public function withPath($path): self
    {
        Type::validate('Path', $path, Type::STRING);

        return $this->getPath() === $path ? $this : (clone $this)->setPath($path);
    }

    /**
     * 返回具有指定查询字符串的实例
     *
     * @param string $query
     * @return static
     * @throws InvalidArgumentException
     */
    public function withQuery($query): self
    {
        Type::validate('Query', $query, Type::STRING);

        return $this->getQuery() === $query ? $this : (clone $this)->setQuery($query);
    }

    /**
     * 返回具有指定片段的实例
     *
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment): self
    {
        Type::validate('Fragment', $fragment, Type::STRING);

        return $this->getFragment() === $fragment ? $this : (clone $this)->setFragment($fragment);
    }

    /**
     * 字符串化
     *
     * @return string
     */
    public function __toString(): string
    {
        $url = '';

        if ('' !== $scheme = $this->getScheme()) {
            $url .= $scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '' || $scheme === 'file') {
            $url .= '//' . $authority;
        }

        $url .= $this->getPath();

        if ('' !== $query = $this->getQuery()) {
            $url .= '?' . $query;
        }

        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    /**
     * 生成用户信息
     *
     * @param string $user
     * @param string|null $pass
     * @return string
     */
    private static function makeUserInfo(string $user, ?string $pass): string
    {
        return $user
            ? $pass
                ? $user . ':' . $pass
                : $user
            : '';
    }

    /**
     * 生成授权信息
     *
     * @param string $userInfo
     * @param string $host
     * @param int|null $port
     * @return string
     */
    private static function makeAuthority(string $userInfo, string $host, ?int $port): string
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

    /**
     * 获取指定组件
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    private function getComponent(string $name, $default = null)
    {
        return $this->components[$name] ?? $default;
    }

    /**
     * 设置方案组件
     *
     * @param string $scheme
     * @return $this
     */
    private function setScheme(string $scheme): self
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * 设置用户信息组件
     *
     * @param string $userInfo
     * @return $this
     */
    private function setUserInfo(string $userInfo): self
    {
        $this->userInfo = $userInfo;
        $this->authority = null;
        return $this;
    }

    /**
     * 设置主机组件
     *
     * @param string $host
     * @return $this
     */
    private function setHost(string $host): self
    {
        $this->host = $host;
        $this->authority = null;
        return $this;
    }

    /**
     * 设置端口组件
     *
     * @param string|null $port
     * @return $this
     */
    private function setPort(?string $port): self
    {
        $this->port = $port;
        $this->authority = null;
        return $this;
    }

    /**
     * 设置路径组件
     *
     * @param string $path
     * @return $this
     */
    private function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 设置查询字符串
     *
     * @param string $query
     * @return $this
     */
    private function setQuery(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * 设置片段组件
     *
     * @param string $fragment
     * @return $this
     */
    private function setFragment(string $fragment): self
    {
        $this->fragment = $fragment;
        return $this;
    }
}
