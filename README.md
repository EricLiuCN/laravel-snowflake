# laravel-snowflake

### 安装 

    composer require ericliucn/laravel-snowflake:"dev-master"


### 生成配置 

    php artisan vendor:publish --provider="Ericliucn\LaravelSnowflake\Providers\SnowflakeServiceProvider"


### 引入 

    use Ericliucn\LaravelSnowflake\Snowflake;
    
    $sn = new Snowflake();
    $id = $sn->nextId();
    
   