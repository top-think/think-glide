# ThinkPHP 图片动态裁剪缩放库

[![Build Status](https://img.shields.io/travis/top-think/think-glide/master.svg?style=flat-square)](https://travis-ci.org/top-think/think-glide)
[![Coverage Status](https://img.shields.io/codecov/c/github/top-think/think-glide.svg?style=flat-square)](https://codecov.io/github/top-think/think-glide)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/think-glide.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/think-glide)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/top-think/think-glide.svg?style=flat-square)](https://scrutinizer-ci.com/g/top-think/think-glide/?branch=master)

[Glide](https://github.com/thephpleague/glide) 是一个可以帮助你根据指定参数动态的生成图片内容给浏览器的图片操作库，从而实现
图片动态裁剪，打水印等，本库对 Glide 进行了一些友好的包装与扩展，屏蔽了原生库的一些底层抽象从而使得 ThinkPHP 用户可以在 ThinkPHP 项目中
更好的添加图片的动态裁剪功能。

## Installation

执行下面命令安装:

```bash
$ composer require slince/think-glide
```

## Usage

### Quick start

由于从 ThinkPHP 5.1.6 开始添加了中间件的功能，所以在
 
- ThinkPHP 5.1.6 及以上版本使用 middleware 注册：

    打开 `application/middleware.php` 文件（如果不存在创建即可），注册 middleware：
    
    ```php
    return [
        //...
    
        \Slince\Glide\GlideMiddleware::factory([
            'source' => __DIR__ . '/../img',
        ])
    ];
    ```
    
    这种方式比较简单，也是推荐的方式；

- ThinkPHP 5.1.0 以上 5.1.6 以下版本：

    不支持middleware，所以启用过程要复杂一点，我们用下面方式来妥协：
    
    ```php
    // 在 /route/route.php 注册下面路由
    Route::get('images/:file', 'index/handleImageRequest');
    
    //在控制器 index 里创建action
    public function handleImageRequest()
    {
        $middleware = \Slince\Glide\GlideMiddleware::factory([
            'source' => App::getRootPath() . '/img',
        ]);
        
        return $middleware(app('request'), function(){
            return app('response');
        });
    }
    ```

`source` 是你本地图片文件夹的位置，假设该目录下有图片 `user.jpg`, 打开浏览器访问下面链接：
 
```
http://youdomain.com/images/user.jpg?w=100&h=100
```
即可得到缩小后的图片。

### 参数说明

| 参数名 | 类型 | 说明 | 是否必选 |
| --- | --- | --- | --- |
| source | string | 本地文件夹位置 | 是 |
| cache| string | 缓存文件位置，默认在 `runtime/glide` 下面| 否 |
| cacheTime| string | 缓存时间，示例 `+2 days`, 缓存期间多次请求会自动响应 304| 否 |
| signKey | string | 安全签名 | 否 | 
| onException | callable | 异常处理handler | 否 | 
| baseUrl | string | 路由前缀，匹配到该前缀时中间件开始执行，默认是 `/images` | 否 | 

### 安全签名

不开启安全签名的情况下用户可以调整query里面的参数自行对图片进行裁剪，如果你不打算这么做的话，你可以通过
`signKey` 进行校验，

```php
\Slince\Glide\GlideMiddleware::factory([
    'source' => __DIR__ . '/../img',
    'signKey' => 'v-LK4WCdhcfcc%jt*VC2cj%nVpu+xQKvLUA%H86kRVk_4bgG8&CWM#k*'
])
```

这种情况下用户自行调整参数将会无效；生成安全的URL:

```php
echo app('glide.url_builder')->getUrl('user.jpg', ['w' => 100, 'h' => 100]);

//你会得到如下链接：/images/user.jpg?w=100&h=100&s=af3dc18fc6bfb2afb521e587c348b904
```

### 异常处理

如果用户访问了一张不存在的图片或者没有进行安全校验，系统会抛出异常，你可以通过 `onException` 进行替换默认行为：

```php
\Slince\Glide\GlideMiddleware::factory([
    'source' => __DIR__ . '/../img',
    'signKey' => 'v-LK4WCdhcfcc%jt*VC2cj%nVpu+xQKvLUA%H86kRVk_4bgG8&CWM#k*'，
    'onException' => function(\Exception $exception, $request, $server){
    
        if ($exception instanceof \League\Glide\Signatures\SignatureException) {
            $response = new Response('签名错误', 403);
        } else {
            $response = new Response(sprintf('你访问的资源 "%s" 不存在', $request->path()), 404);
        }
        
        return $response;
    }
])
```

注意该闭包必须返回一个 `think\Response` 实例；

### Quick reference

不止支持裁剪，glide还支持其它操作，只要传递对应参数即可，参考这里查看支持的参数：

[http://glide.thephpleague.com/1.0/api/quick-reference/](http://glide.thephpleague.com/1.0/api/quick-reference/)  

## Changelog

See [CHANGELOG.md](./CHANGELOG.md)

## License

See [MIT](https://opensource.org/licenses/MIT).
