<?php

namespace Slince\Glide\Tests;

use League\Glide\Server;
use League\Glide\Signatures\SignatureException;
use PHPUnit\Framework\TestCase;
use Slince\Glide\GlideMiddleware;
use think\Container;
use think\Request;
use think\Response;

class GlideMiddlewareTest extends TestCase
{
    const CACHE_DIR =  __DIR__ . '/fixtures/cache';

    public function setUp()
    {
        exec('rm -rf ' . static::CACHE_DIR . '/phpdish.png');
    }

    public function testFactory()
    {
        $this->assertInstanceOf(\Closure::class, GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache'
        ]));
    }

    public function testResponse()
    {
        $request = (new Request())->create('/images/phpdish.png?w=50&h=100');

        $middleware = GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache'
        ]);

        $response = $middleware($request, function($request){
            return new Response('ok');
        });

        $this->assertFileExists(static::CACHE_DIR . '/phpdish.png');
        $this->assertGreaterThan(0, $response->getHeader('Content-Length'));
    }

    public function testIgnoreMiddleware()
    {
        $request = (new Request())->create('/phpdish.png');

        $middleware = GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache'
        ]);

        $response = $middleware($request, function($request){
            return new Response('ok');
        });

        $this->assertEquals('ok', $response->getContent());
    }

    public function testException()
    {
        $request = (new Request())->create('/images/phpdish.jpg');

        $middleware = GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache'
        ]);

        $this->expectException(\League\Flysystem\FileNotFoundException::class);

        $middleware($request, function($request){
            return new Response('ok');
        });

    }

    public function testVerifySign()
    {
        $request = (new Request())->create('/images/phpdish.jpg');

        $middleware = GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache',
            'cacheTime' => '+2 days',
            'signKey' => 'helloworld'
        ]);

        $generated = Container::get('glide.url_builder')->getUrl('phpdish.png', ['w' => 50]);
        $query = parse_url($generated, PHP_URL_QUERY);
        parse_str($query, $junks);
        $this->assertArrayHasKey('s', $junks);
        $this->assertEquals(50, $junks['w']);

        try {
            $middleware(
                $request,
                function ($request) {
                    return new Response('ok');
                }
            );
            $this->fail('bad expected exception');
        } catch (SignatureException $exception) {
        }

        $request = (new Request())->create($generated);
        $middleware($request, function ($request) {
            return new Response('ok');
        });

        try {
            $middleware(
                (new Request())->create('/images/phpdish.jpg?s=45667777'),
                function ($request) {
                    return new Response('ok');
                }
            );
            $this->fail('bad expected exception');
        } catch (SignatureException $exception) {
        }
    }

    public function testCachingHeaders()
    {
        $request = (new Request())->create('/images/phpdish.png');

        $middleware = GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache',
            'cacheTime' => '+2 days'
        ]);
        $response = $middleware($request, function ($request) {
            return new Response('ok');
        });
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue((boolean)$response->getHeader('Last-Modified'));

        //带上头信息
        $request->header('If-Modified-Since', $response->getHeader('Last-Modified'));
        $request = (new Request())->create('/images/phpdish.png', 'GET', [],[],[], [
            'HTTP_If_Modified_Since' => $response->getHeader('Last-Modified')
        ]);
        $response = $middleware($request, function ($request) {
            return new Response('ok');
        });
        $this->assertEquals(304, $response->getCode());
    }

    public function testOnException()
    {
        $middleware = GlideMiddleware::factory([
            'source' => __DIR__ . '/fixtures/source',
            'cache' => __DIR__ . '/fixtures/cache',
            'cacheTime' => '+2 days',
            'signKey' => 'helloworld',
            'onException' => function(\Exception $exception, Request $request, Server $server){
                if ($exception instanceof \League\Glide\Signatures\SignatureException) {
                    $response = new Response('签名错误', 500);
                } else {
                    $response = new Response(sprintf('你访问的资源 "%s" 不存在', $request->path()), 404);
                }
                return $response;
            }
        ]);
        $request = (new Request())->create(Container::get('glide.url_builder')->getUrl('non-exists.png', ['w' => 50]));
        $response = $middleware($request, function ($request) {
            return new Response('ok');
        });
        $this->assertContains('不存在', $response->getContent());

        $request = (new Request())->create('/images/phpdish.png?w=50');
        $response = $middleware($request, function ($request) {
            return new Response('ok');
        });
        $this->assertContains('签名错误', $response->getContent());
    }
}
