<?php

declare(strict_types=1);

namespace chaser\http\message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * 数据流
 *
 * @package chaser\http\message
 */
class Stream implements StreamInterface
{
    /**
     * 可读模式正则
     */
    public const PREG_READABLE = '/r|[waxc]b?\+/';

    /**
     * 可写模式正则
     */
    public const PREG_WRITABLE = '/rb?\+|[waxc]/';

    /**
     * 数据流
     *
     * @var resource|null
     */
    protected $stream;

    /**
     * 流元数据数组
     *
     * @var array|null
     */
    protected ?array $meta;

    /**
     * 关联 URI 或文件名
     *
     * @var string|null
     */
    protected ?string $uri;

    /**
     * 数据长度
     *
     * @var int|null
     */
    protected ?int $size;

    /**
     * 是否可查找
     *
     * @var bool
     */
    protected bool $seekable;

    /**
     * 是否可读
     *
     * @var bool
     */
    protected bool $readable;

    /**
     * 是否可写
     *
     * @var bool
     */
    protected bool $writable;

    /**
     * 从字符串创建新流
     *
     * @param string $content
     * @return Stream
     */
    public static function create(string $content = ''): Stream
    {
        $resource = fopen('php://temp', 'rw+');
        fwrite($resource, $content);
        return new self($resource);
    }

    /**
     * 从现有文件创建流
     *
     * @param string $filename
     * @param string $mode
     * @return Stream
     */
    public static function createFormFile(string $filename, string $mode = 'r'): Stream
    {
        if ($mode === '' || strpos('rwaxc', $mode[0]) === false) {
            throw new InvalidArgumentException(sprintf('The mode %s is invalid.', $mode));
        }

        if ($filename === '') {
            throw new RuntimeException('Filename cannot be empty.');
        }

        if (false === $resource = @fopen($filename, $mode)) {
            throw new RuntimeException(sprintf('The file "%s" cannot be opened.', $filename));
        }

        return new self($resource);
    }

    /**
     * 初始化流信息
     *
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource.');
        }

        $this->stream = $stream;

        if (fseek($this->stream, 0, SEEK_CUR) === -1) {
            $this->seekable = false;
        }

        $this->uri = $this->getMetadata('uri');
    }

    /**
     * 读取（从头至尾）全部数据
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * 关闭流和底层资源
     *
     * @return void
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * 分离底层资源
     *
     * @return resource|null
     */
    public function detach()
    {
        if (isset($this->stream)) {
            $this->size = null;
            $this->uri = null;
            $this->seekable = false;
            $this->readable = false;
            $this->writable = false;

            $resource = $this->stream;
            unset($this->stream);

            return $resource;
        }

        return null;
    }

    /**
     * 获取流的大小
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        if (!isset($this->size) && isset($this->stream)) {
            if ($this->uri) {
                clearstatcache(true, $this->uri);
            }
            $this->size = fstat($this->stream)['size'] ?? null;
        }
        return $this->size;
    }

    /**
     * 返回流指针计数
     *
     * @inheritDoc
     */
    public function tell(): int
    {
        if (false === $tell = ftell($this->stream)) {
            throw new RuntimeException('Unable to determine stream position.');
        }
        return $tell;
    }

    /**
     * 判断流指针是否到末尾
     *
     * @return bool
     */
    public function eof(): bool
    {
        return !isset($this->stream) || !is_resource($this->stream) || feof($this->stream);
    }

    /**
     * 返回流是否可查找
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable ??= $this->getMetadata('seekable');
    }

    /**
     * 移动指针
     *
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException(sprintf('Unable to seek to stream position "%d" with whence "%d".', $offset, $whence));
        }
    }

    /**
     * 指针指向流的开头
     *
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * 返回流是否可写
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->readable ??= preg_match(self::PREG_WRITABLE, $this->getMetadata('mode')) > 0;
    }

    /**
     * 将数据写入流
     *
     * @inheritDoc
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('Cannot write to a non-writable stream.');
        }

        $this->size = null;

        if (false === $write = fwrite($this->stream, $string)) {
            throw new RuntimeException('Unable to write to stream.');
        }

        return $write;
    }

    /**
     * 返回流是否可读
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->readable ??= preg_match(self::PREG_READABLE, $this->getMetadata('mode')) > 0;
    }

    /**
     * 从流中读取数据
     *
     * @inheritDoc
     */
    public function read($length): string
    {
        if ($this->isReadable()) {
            return fread($this->stream, $length);
        }
        throw new RuntimeException('Cannot read to a unreadable stream.');
    }

    /**
     * 读取剩余内容
     *
     * @inheritDoc
     */
    public function getContents(): string
    {
        if (isset($this->stream)) {
            $content = stream_get_contents($this->stream);
            if ($content !== false) {
                return $content;
            }
        }
        throw new RuntimeException('Unable to read stream contents.');
    }

    /**
     * 获取流元素据数组或指定元素据
     *
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        if (isset($this->stream)) {
            if (!isset($this->meta)) {
                $this->meta = stream_get_meta_data($this->stream);
            }
            return $key === null ? $this->meta : $this->meta[$key] ?? null;
        }
        return $key === null ? [] : null;
    }

    /**
     * 析构函数：关闭资源
     */
    public function __destruct()
    {
        $this->close();
    }
}
