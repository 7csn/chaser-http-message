<?php

declare(strict_types=1);

namespace chaser\http\message;

use chaser\http\message\traits\MessageTrait;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * http 服务器响应
 *
 * @package chaser\http\message
 *
 * @method static withProtocolVersion($version)
 * @method static withHeader($name, $value)
 * @method static withAddedHeader($name, $value)
 * @method static withoutHeader($name)
 * @method static withBody(StreamInterface $body)
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    /**
     * 状态码说明
     *
     * @var string[]
     */
    public const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * 状态码
     *
     * @var int
     */
    protected int $statusCode = 200;

    /**
     * 原因短语
     *
     * @var string
     */
    protected string $reasonPhrase = '';

    /**
     * 初始化消息数据
     *
     * @param int|null $code
     * @param string|null $reasonPhrase
     * @param array|null $headers
     * @param StreamInterface|null $body
     * @param string|null $protocolVersion
     */
    public function __construct(
        int $code = null,
        string $reasonPhrase = null,
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
        if ($code !== null) {
            $this->setStatus($code, $reasonPhrase ?? '');
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        Argument::validate('Code', $code, Argument::INT);
        Argument::validate('Reason phrase', $reasonPhrase, Argument::STRING);

        if ($this->statusCode === $code) {

            // 状态码和描述一致不修改
            if ($this->reasonPhrase === $reasonPhrase) {
                return $this;
            }

            // 状态码一致，描述为空，原描述为标准描述则不修改
            if ($reasonPhrase === '' && isset(self::PHRASES[$code])) {
                $reasonPhrase = self::PHRASES[$code];
                if ($reasonPhrase === $this->reasonPhrase) {
                    return $this;
                }
            }
        }

        return (clone $this)->setStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase ??= self::PHRASES[$this->statusCode] ?? '';
    }

    /**
     * 字符串化
     *
     * @return string
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * 设置响应状态
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return $this
     */
    private function setStatus(int $code, string $reasonPhrase = ''): self
    {
        if (!isset(self::PHRASES[$code])) {
            throw new InvalidArgumentException('Invalid status code provided for response');
        }

        $this->statusCode = $code;

        $this->reasonPhrase = $reasonPhrase === '' ? self::PHRASES[$code] : $reasonPhrase;

        return $this;
    }
}
